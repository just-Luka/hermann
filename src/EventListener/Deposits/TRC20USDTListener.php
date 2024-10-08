<?php

declare(strict_types=1);

namespace App\EventListener\Deposits;

use App\Entity\CapitalAccount;
use App\Entity\CryptoWallet;
use App\Entity\QueuedCapitalDeposit;
use App\Entity\QueuedDeposit;
use App\Entity\Transaction;
use App\Event\DepositEvent;
use App\Repository\CapitalAccountRepository;
use App\Repository\CryptoWalletRepository;
use App\Service\Crypto\Tron\TronAccountService;
use App\Trait\CalculationTrait;
use DateTimeImmutable;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class TRC20USDTListener
{
    use CalculationTrait;

    private LoggerInterface $logger;
    private TronAccountService $tronAccountService;
    private EntityManagerInterface $entityManager;
    private CryptoWalletRepository $cryptoWalletRepository;
    private CapitalAccountRepository $capitalAccountRepository;

    public function __construct(
        LoggerInterface $logger,
        TronAccountService $tronAccountService,
        EntityManagerInterface $entityManager,
        CryptoWalletRepository $cryptoWalletRepository,
        CapitalAccountRepository $capitalAccountRepository,
    )
    {
        $this->logger = $logger;
        $this->tronAccountService = $tronAccountService;
        $this->entityManager = $entityManager;
        $this->cryptoWalletRepository = $cryptoWalletRepository;
        $this->capitalAccountRepository = $capitalAccountRepository;
    }

    public function onTransfer(DepositEvent $event): void
    {
        if ($event->getNetwork() !== CryptoWallet::NETWORK_TRC20 && 
        $event->getCoinName() !== CryptoWallet::COIN_NAME_USDT) {
            return;
        }

        $transactions = $this->tronAccountService->getTRC20Transactions($event->getAddressBase58());
        if (is_null($transactions)) {
            return;
        }

        foreach ($transactions as $transaction) {
            if ($transaction['token_info']['symbol'] !== 'USDT') {
                continue;
            }
            
            $this->entityManager->beginTransaction(); ### Begin transactions

            try {
                ### Add Transaction
                $transactionEntity = new Transaction();
                $transactionEntity->setCreatedAt(new DateTimeImmutable());
                $transactionEntity->setUpdatedAt(new DateTimeImmutable());
                $transactionEntity->setExTransactionId($transaction['transaction_id']);
                $transactionEntity->setSymbol($transaction['token_info']['symbol']);
                $transactionEntity->setTokenAddress($transaction['token_info']['address']);
                $transactionEntity->setDecimals($transaction['token_info']['decimals']);
                $transactionEntity->setBlockTimestamp($transaction['block_timestamp']);
                $transactionEntity->setFrom($transaction['from']);
                $transactionEntity->setTo($transaction['to']);
                $transactionEntity->setExType($transaction['type']);
                $transactionEntity->setValue($transaction['value']);
                $transactionEntity->setStatus(Transaction::STATUS_COMPLETED);
                $transactionEntity->setType(Transaction::TYPE_DEPOSIT);
                $this->entityManager->persist($transactionEntity);

                ### Update crypto wallet balance
                $cryptoWalletRepository = $this->cryptoWalletRepository->findOneBy(['address_base58' => $transaction['to']]);
                $newBalance = $this->addUSDBalance((float) $cryptoWalletRepository->getBalance(), (int) $transaction['value'], $transaction['token_info']['decimals']);
                $cryptoWalletRepository->setBalance((string) $newBalance);
                $this->entityManager->persist($cryptoWalletRepository);
                
                $user = $cryptoWalletRepository->getUser();
                $capitalAccount = $user->getCapitalAccount();

                if (is_null($capitalAccount)) {
                    ### User's first deposit

                    $capitalAccount = $this->getRecommendedAccount();
                    $capitalAccount->setAssignedUsersCount($capitalAccount->getAssignedUsersCount() + 1);

                    $user->setCapitalAccount($capitalAccount);
                }

                $capitalBalance = $capitalAccount->getAvailableBalance();

                $depositAmount = ((int) $transaction['value'] / (10 ** $transaction['token_info']['decimals']));
                if ($capitalBalance < $depositAmount) {
                    ### We dont have enough balance on capital and need to be deposited
                    $queuedCapitalDeposit = new QueuedCapitalDeposit();
                    $queuedCapitalDeposit->setCapitalAccount($capitalAccount);
                    $queuedCapitalDeposit->setCreatedAt(new DateTimeImmutable());
                    $queuedCapitalDeposit->setUpdatedAt(new DateTimeImmutable());
                    $queuedCapitalDeposit->setAmount((string) abs($capitalBalance - $depositAmount));
                    $queuedCapitalDeposit->setStatus(QueuedCapitalDeposit::STATUS_AWAITING);

                    $this->entityManager->persist($queuedCapitalDeposit);
                } else {
                    $capitalAccount->setAvailableBalance($this->minusUSDBalance($capitalBalance, (int) $transaction['value'], $transaction['token_info']['decimals']));
                    $capitalAccount->setAllocatedBalance($this->addUSDBalance($capitalAccount->getAllocatedBalance(), (int) $transaction['value'], $transaction['token_info']['decimals']));

                    ### Update user balance
                    $user = $cryptoWalletRepository->getUser();
                    $newBalance = $this->addUSDBalance($user->getBalance(), (int) $transaction['value'], $transaction['token_info']['decimals']);
                    $user->setBalance($newBalance);
                    $this->entityManager->persist($user);
                }

                $queuedDeposit = $event->getQueuedDeposit();
                $queuedDeposit->setStatus(QueuedDeposit::STATUS_PAYED_OK);

                $this->entityManager->persist($capitalAccount);
                $this->entityManager->persist($queuedDeposit);

                $this->entityManager->flush();
                $this->entityManager->commit();
            } catch(UniqueConstraintViolationException $e) { 
                // Catch case when trying to add a duplicate transaction
                $this->entityManager->rollback();
            } catch (\Exception $e) {
                $this->entityManager->rollback();
                $this->logger->critical('Deposit checking failed: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * TODO User-Account assigning algorithm
     *
     * @return CapitalAccount
     */
    private function getRecommendedAccount(): CapitalAccount
    {
        $accounts = $this->capitalAccountRepository->findBy(['restrict_user_assign' => false]);

        # $availableAccount = []
        #
        foreach ($accounts as $account) {
            if ($account->getIsMain()) {
                return $account;
            }
        }
    }
}
