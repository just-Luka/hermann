<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot\Command;

use App\Entity\CommandQueueStorage;
use App\Entity\User;
use App\Repository\CommandQueueStorageRepository;
use App\Repository\UserRepository;
use App\Service\Telegram\Bot\TradingBotService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final class TradingBotCommand
{
    private LoggerInterface $logger;
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;
    private TradingBotService $tradingBotService;
    private CommandQueueStorageRepository $cmdQueueRepository;
    private ?int $chatId;
    private ?array $sender;

    public function __construct(
        LoggerInterface $logger, 
        UserRepository $userRepository,
        CommandQueueStorageRepository $cmdQueueRepository,
        EntityManagerInterface $entityManager,
        TradingBotService $tradingBotService,
    )
    {
        $this->logger = $logger;
        $this->userRepository = $userRepository;
        $this->cmdQueueRepository = $cmdQueueRepository;
        $this->entityManager = $entityManager;
        $this->tradingBotService = $tradingBotService;
    }

    public function setup(int $chatId, array $sender): void
    {
        $this->chatId = $chatId;
        $this->sender = $sender;
    }

    public function start(): void
    {
        $sender = $this->sender;
        $user = $this->userRepository->findByTelegramId($sender['id']);

        if (! $user) {
            $user = new User();
            $user->setTelegramId((string) $sender['id']);
            $user->setCreatedAt(new DateTimeImmutable());
        }

        $user->setFirstName($sender['first_name'] ?? $sender['username'] ?? 'Guest');
        $user->setLastName($sender['last_name'] ?? null);
        $user->setUsername($sender['username'] ?? null);
        $user->setTelegramChatId((string) $this->chatId);
        $user->setPhotoUrl($sender['photo_url'] ?? null);
        $user->setUpdatedAt(new DateTimeImmutable());
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $firstName = $user->getFirstName();
        $currentBalance = $user->getBalance();

        $message = "
🟩🟥
Welcome to Hermann Trading!

Here, with just a few commands, you can
<b>BUY</b> and <b>SELL</b> more than 3,000 assets.
Trade: Crypto, Stocks, Forex, Indices, Commodities and more ...

------------------------------
Account Details:
Name: $firstName
Current Balance: <b>$$currentBalance</b>
Margin Balance: <b>$0</b>
Assets: <b>0</b>
------------------------------

Available Commands:
1. Check profile and balance: /me
2. Make a deposit: /deposit
3. Withdraw funds: /withdraw
4. Open a new trade: /open
5. Close an active trade: /close
6. Edit an open trade: /edit
7. View recent activities: /history
8. Exit from any process /exit

==============================
";
        $this->tradingBotService->sendMessage($this->chatId, $message);
    }

    // Must be setup security protocol: 
    // User Blocking
    public function open(): void
    {
        $sender = $this->sender;
        $user = $this->userRepository->findByTelegramId($sender['id']);

        $instructions = [
            'type' => 'open',
            'assets' => [],
            'asset' => '',
            'size' => '',
        ];

        $commandQueueStorage = new CommandQueueStorage();
        $commandQueueStorage->setUser($user);
        $commandQueueStorage->setCommandName(__FUNCTION__);
        $commandQueueStorage->setLastQuestion(CommandQueueStorage::QUESTION_SEARCH_ASSET);
        $commandQueueStorage->setInstructions($instructions);
        $commandQueueStorage->setCount(0);
        $commandQueueStorage->setCreatedAt(new DateTimeImmutable());
        $commandQueueStorage->setUpdatedAt(new DateTimeImmutable());

        $this->entityManager->persist($commandQueueStorage);
        $this->entityManager->flush();

        $message = "
Search the asset you want to acqurie.

For example
------------------------------
Type: <b>Bitcoin</b>, <b>ETH</b>, <b>Tesla</b>, <b>USD/EUR</b>, <b>Gold</b>
";
        $this->tradingBotService->sendMessage($this->chatId, $message);
    }

    public function deposit(): void
    {
        $sender = $this->sender;
        $user = $this->userRepository->findByTelegramId($sender['id']);

        $instructions = [
            'type' => 'deposit',
            'payment_methods' => [
                'USDT',
                // 'BTC',
                // 'ETH',
            ],
            'choosen_payment' => '',
        ];

        $commandQueueStorage = new CommandQueueStorage();
        $commandQueueStorage->setUser($user);
        $commandQueueStorage->setCommandName(__FUNCTION__);
        $commandQueueStorage->setLastQuestion(CommandQueueStorage::QUESTION_DEPOSIT);
        $commandQueueStorage->setInstructions($instructions);
        $commandQueueStorage->setCount(0);
        $commandQueueStorage->setCreatedAt(new DateTimeImmutable());
        $commandQueueStorage->setUpdatedAt(new DateTimeImmutable());

        $this->entityManager->persist($commandQueueStorage);
        $this->entityManager->flush();

        $message = "
Please choose a deposit method:

1️. <b>USDT - TRC-20</b>
2️. <b>Bitcoin (Soon)</b>
3️. <b>Ethereum (Soon)</b>
";
        $this->tradingBotService->sendMessage($this->chatId, $message);
    }

    public function exit($hideMessage = false): void
    {
        $sender = $this->sender;
        $user = $this->userRepository->findByTelegramId($sender['id']);

        $storage = $this->cmdQueueRepository->findOneBy(['user' => $user]);

        if ($storage) {
            $this->entityManager->remove($storage);
            $this->entityManager->flush();
        }

        if (! $hideMessage) {
            $message = "
Exited. ✅
            ";
            
            $this->tradingBotService->sendMessage($this->chatId, $message);
        }
    }
}
