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
use App\Service\Telegram\Bot\TradingBotService;
use App\Trait\CalculationTrait;
use DateTimeImmutable;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final readonly class TRC20USDTListener
{
    use CalculationTrait;

    public function __construct(
        private LoggerInterface $logger,
        private TronAccountService $tronAccountService,
        private EntityManagerInterface $entityManager,
        private CryptoWalletRepository $cryptoWalletRepository,
        private CapitalAccountRepository $capitalAccountRepository,
        private TradingBotService $tradingBotService,
    ) {}

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
            
            try {
                $this->entityManager->beginTransaction(); ### Begin transactions

                ### Add Transaction
                $transactionEntity = (new Transaction())
                    ->setCreatedAt(new DateTimeImmutable())
                    ->setUpdatedAt(new DateTimeImmutable())
                    ->setExTransactionId($transaction['transaction_id'])
                    ->setSymbol($transaction['token_info']['symbol'])
                    ->setTokenAddress($transaction['token_info']['address'])
                    ->setDecimals($transaction['token_info']['decimals'])
                    ->setBlockTimestamp($transaction['block_timestamp'])
                    ->setFrom($transaction['from'])
                    ->setTo($transaction['to'])
                    ->setExType($transaction['type'])
                    ->setValue($transaction['value'])
                    ->setStatus(Transaction::STATUS_COMPLETED)
                    ->setType(Transaction::TYPE_DEPOSIT);

                $this->entityManager->persist($transactionEntity);

                ### Update crypto wallet balance
                $cryptoWalletRepository = $this->cryptoWalletRepository->findOneBy(['address_base58' => $transaction['to']]);
                $newBalance = $this->addUSDBalance((float) $cryptoWalletRepository->getBalance(), (int) $transaction['value'], $transaction['token_info']['decimals']);
                $cryptoWalletRepository->setBalance((string) $newBalance);
                $cryptoWalletRepository->setLastTransactionAt(new DateTimeImmutable());
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
                    $queuedCapitalDeposit = (new QueuedCapitalDeposit())
                        ->setCapitalAccount($capitalAccount)
                        ->setCreatedAt(new DateTimeImmutable())
                        ->setUpdatedAt(new DateTimeImmutable())
                        ->setAmount((string) abs($capitalBalance - $depositAmount))
                        ->setStatus(QueuedCapitalDeposit::STATUS_AWAITING);

                    $this->entityManager->persist($queuedCapitalDeposit);

                    $message = "⏳ Your deposit <b>\$$depositAmount</b> is processing...";
                } else {
                    $capitalAccount->setAvailableBalance($this->minusUSDBalance($capitalBalance, (int) $transaction['value'], $transaction['token_info']['decimals']));
                    $capitalAccount->setAllocatedBalance($this->addUSDBalance($capitalAccount->getAllocatedBalance(), (int) $transaction['value'], $transaction['token_info']['decimals']));

                    ### Update user balance
                    $newBalance = $this->addUSDBalance($user->getBalance(), (int) $transaction['value'], $transaction['token_info']['decimals']);
                    $user->setBalance($newBalance);
                    $this->entityManager->persist($user);
                    $message = "✅ Your deposit <b>\$$depositAmount</b> has been processed!";
                }

                $queuedDeposit = $event->getQueuedDeposit();
                $queuedDeposit->setStatus(QueuedDeposit::STATUS_PAYED_OK);

                $this->entityManager->persist($capitalAccount);
                $this->entityManager->persist($queuedDeposit);

                $this->entityManager->flush();
                $this->entityManager->commit();

                $this->tradingBotService->sendMessage((int) $user->getTelegramChatId(), $message);
            } catch (UniqueConstraintViolationException $e) {
                $this->entityManager->rollback();
        
                // Log the error
                $this->logger->critical('Deposit checking failed: Duplicate transaction ID.');
        
                throw new \Exception('Duplicate transaction ID.');
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
