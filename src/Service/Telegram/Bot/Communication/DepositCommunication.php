<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot\Communication;

use App\Entity\CommandQueueStorage;
use App\Entity\CryptoWallet;
use App\Entity\QueuedDeposit;
use App\Entity\User;
use App\Repository\CryptoWalletRepository;
use App\Service\Crypto\Tron\TronAccountService;
use App\Service\Telegram\Bot\TradingBotService;
use App\Trait\Message\OpenMessageTrait;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final class DepositCommunication
{
    use OpenMessageTrait;

    private $logger;
    private $tradingBotService;
    private $chatId;
    private $entityManager;
    private $commandQueueStorage;
    private $user;
    private TronAccountService $tronAccountService;
    private CryptoWalletRepository $cryptoWalletRepository;

    private array $limit = [
        'choosing_deposit' => 3,
    ];

    public array $fixedDeposit = [
        25,
        50,
        100,
        250,
        500,
        1000, // 6
    ];

    public function __construct(
        LoggerInterface $logger, 
        TradingBotService $tradingBotService,
        EntityManagerInterface $entityManager,
        TronAccountService $tronAccountService,
        CryptoWalletRepository $cryptoWalletRepository,
    )
    {
        $this->logger = $logger;
        $this->tradingBotService = $tradingBotService;
        $this->entityManager = $entityManager;
        $this->tronAccountService = $tronAccountService;
        $this->cryptoWalletRepository = $cryptoWalletRepository;
    }

    public function setup(int $chatId, CommandQueueStorage $commandQueueStorage, User $user): void
    {
        $this->chatId = $chatId;
        $this->commandQueueStorage = $commandQueueStorage;
        $this->user = $user;
    }

    public function amount(string $number): void
    {        
        if (! is_numeric($number) || (int) $number > 3) {
            $message = "
Wrong deposit method. 
Please choose a correct <b>number</b>
            ";
            $this->tradingBotService->sendMessage($this->chatId, $message);
            return;
        }

        if (in_array((int) $number, [2, 3])) { // Only support USDT
            $message = "
Under maintenance 🔧
            ";
            $this->tradingBotService->sendMessage($this->chatId, $message);
            return;
        }

        if ((int) $number === 1) { // USDT
            $message = "Please select an amount or type your own:\n";
    
            foreach ($this->fixedDeposit as $index => $amount) {
                $message .= ($index + 1) . ". $" . $amount . "\n";
            }
            
            $message .= "\nOr type your <b>custom</b> amount.\n\n";
            $message .= "<i>Click /exit to cancel</i>";
            $this->tradingBotService->sendMessage($this->chatId, $message);
            
            $instructions = $this->commandQueueStorage->getInstructions();
            $instructions['choosen_payment'] = $instructions['payment_methods'][(int) $number - 1];

            $this->commandQueueStorage->setLastQuestion(CommandQueueStorage::QUESTION_TYPING_USD_AMOUNT);
            $this->commandQueueStorage->setInstructions($instructions);

            $this->entityManager->persist($this->commandQueueStorage);
            $this->entityManager->flush();
        }
    }

    public function createCryptoPayment(string $number): void
    {
        $number = str_replace('$', '', $number);

        if (! is_numeric($number)) {
            $message = "
Wrong deposit method. 
Please choose a correct <b>number</b>
            ";
            $this->tradingBotService->sendMessage($this->chatId, $message);
            return;
        }

        if ((int) $number <= 0 || ((int) $number < 25 && (int) $number > count($this->fixedDeposit)) || 20000 < (int) $number) {
            $message = "
Min Available Deposit: <b>$25</b>
Max Available Deposit: <b>$20,000</b>
";
            $this->tradingBotService->sendMessage($this->chatId, $message);
            return;
        }

        $instructions = $this->commandQueueStorage->getInstructions();
        $instructions['amount'] = isset($this->fixedDeposit[$number - 1]) ? $this->fixedDeposit[$number - 1] : $number;

        $lastWallet = $this->cryptoWalletRepository->findLastCreatedWalletByUser($this->user);

        // TODO, generate new address if old address was created more then 24h ago.

        if (is_null($lastWallet)) { // If User is new and still not have any wallet
            $createdWallet = $this->tronAccountService->createWallet();
            if (is_null($createdWallet)) {
                $this->logger->critical('USDT wallet can not be created!');
                return;
            }

            // TODO check if $createdWallet['isValid']

            $cryptoWallet = new CryptoWallet();
            $cryptoWallet->setUser($this->user);
            $cryptoWallet->setCreatedAt(new DateTimeImmutable());
            $cryptoWallet->setUpdatedAt(new DateTimeImmutable());
            $cryptoWallet->setCoinName(CryptoWallet::COIN_NAME_USDT);
            $cryptoWallet->setNetwork(CryptoWallet::NETWORK_TRC20);
            $cryptoWallet->setAddressBase58($createdWallet['data']['address_base58']);
            $cryptoWallet->setAddressHex($createdWallet['data']['address_hex']);
            $cryptoWallet->setPrivateKey($createdWallet['data']['private_key']);
            $cryptoWallet->setPublicKey($createdWallet['data']['public_key']);
            $cryptoWallet->setBalance('0');

            $this->entityManager->persist($cryptoWallet);
            $this->entityManager->flush();

            $lastWallet = $cryptoWallet;
        }

        $message = "
<b>To process your Tether (USDT) TRC-20 payment successfully:</b>

- <b>Send the exact USDT amount</b> to the specified address.
- <b>Use only Tron network</b> for your transfer.
- <b>Generate a new payment for</b> each deposit.

==========================
Amount: <b>\${$instructions['amount']}</b>
Address (Tron): <b>{$lastWallet->getAddressBase58()}</b>
==========================

Processing takes up to 10-15 minutes.
        ";
        $this->tradingBotService->sendMessage($this->chatId, $message);
        $this->tradingBotService->sendMessage($this->chatId, $lastWallet->getAddressBase58());

        $queuedDeposit = new QueuedDeposit();
        $queuedDeposit->setCryptoWallet($lastWallet);
        $queuedDeposit->setCreatedAt(new DateTimeImmutable());
        $queuedDeposit->setUpdatedAt(new DateTimeImmutable());
        $queuedDeposit->setAmount((string) $instructions['amount']);

        $this->entityManager->persist($queuedDeposit);
        $this->entityManager->flush();

        // Exit after success
        // if ($this->commandQueueStorage) {
        //     $this->entityManager->remove($this->commandQueueStorage);
        //     $this->entityManager->flush();
        // }
    }
}

