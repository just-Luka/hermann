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
use App\Trait\Message\DepositMessageTrait;
use App\Trait\Message\OpenMessageTrait;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final class DepositCommunication
{
    use DepositMessageTrait;

    private int $chatId;
    private CommandQueueStorage $commandQueueStorage;
    private User $user;

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
        private readonly LoggerInterface $logger,
        private readonly TradingBotService $tradingBotService,
        private readonly EntityManagerInterface $entityManager,
        private readonly TronAccountService $tronAccountService,
        private readonly CryptoWalletRepository $cryptoWalletRepository,
    ) {}

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
        $instructions['amount'] = $this->fixedDeposit[$number - 1] ?? $number;

        $userWallet = $this->cryptoWalletRepository->findLastCreatedWalletByUser($this->user);

        if (is_null($userWallet)) {
            ### If user doesn't have wallet
            $this->tronAccountService->requestWalletCreation($this->user);
            # TODO send notification to telegram and perform queue actions
            return;
        }

        $this->tradingBotService->sendMessage($this->chatId, $this->createCryptoPaymentMessage($instructions, $userWallet));
        $this->tradingBotService->sendMessage($this->chatId, $userWallet->getAddressBase58());

        $queuedDeposit = new QueuedDeposit();
        $queuedDeposit->setCryptoWallet($userWallet);
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

