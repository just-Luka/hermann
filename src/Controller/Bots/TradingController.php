<?php

declare(strict_types=1);

namespace App\Controller\Bots;

use App\Entity\CommandQueueStorage;
use App\Event\HermannPaymentsEvent;
use App\Repository\CommandQueueStorageRepository;
use App\Repository\UserRepository;
use App\Service\Crypto\Tron\TronAccountService;
use App\Service\Telegram\Bot\Communication\OpenCommunication;
use App\Service\Telegram\Bot\Command\TradingBotCommand;
use App\Service\Telegram\Bot\Communication\DepositCommunication;
use App\Service\Translation\TranslationService;
use App\Trait\Message\Formatter\MessageFormatterTrait;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

final class TradingController extends AbstractController
{
    use MessageFormatterTrait;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly TranslationService $translationService
    ) {}

    public function handleWebhook(
        Request $request, 
        TradingBotCommand $tradingBotCommand, 
        CommandQueueStorageRepository $commandQueueStorage, 
        UserRepository $userRepository, 
        OpenCommunication $openCommunication,
        DepositCommunication $depositCommunication,
    ): JsonResponse
    {
        try {
            $update = json_decode($request->getContent(), true);

            if (isset($update['message'])) {
                $chatId = $update['message']['chat']['id'];
                $command = ltrim($update['message']['text'], '/');
    
                $sender = $update['message']['from'];
                $languageCode = $sender['language_code'];
                $telegramId = $sender['id'];
    
                if ($sender['is_bot']) {
                    return $this->json('Bot communication not supported yet!');
                }
    
                $tradingBotCommand->setup($chatId, $sender);
    
                if ($command === 'start') {
                    $tradingBotCommand->exit(true);
                    $tradingBotCommand->start();
                    return $this->json('OK');
                } elseif($command === 'open') {
                    $tradingBotCommand->exit(true);
                    $tradingBotCommand->open();
                    return $this->json('OK');
                } elseif($command === 'deposit') {
                    $tradingBotCommand->exit(true);
                    $tradingBotCommand->deposit();
                    return $this->json('OK');
                } elseif($command === 'exit') {
                    $tradingBotCommand->exit();
                    return $this->json('OK');
                } 
    
                // If there is no match for any command, then :
                $text = $command;
                
                // Check if user in process of communication.
                // That means he already typed some /command, our bot waits for something!
                $user = $userRepository->findByTelegramId($telegramId);
                $storage = $commandQueueStorage->findOneBy(['user' => $user]);
    
                // Refactoring: new class for executing sub commands
                
                if ($storage) {
                    $openCommunication->setup($chatId, $storage, $user);
                    if ($storage->getCommandName() === 'open') {
                        if ($storage->getLastQuestion() === CommandQueueStorage::QUESTION_SEARCH_ASSET) {
                            $openCommunication->searchAsset($text);
                        } elseif ($storage->getLastQuestion() === CommandQueueStorage::QUESTION_CHOOSING_ASSET) {
                            if (!is_numeric($text) || (int)$text < 1 || (int)$text > sizeof($storage->getInstructions()['assets'])) {
                                $openCommunication->searchAsset($text);
                            } else {
                                $openCommunication->createOrder((int) $text);
                            }
                        } elseif ($storage->getLastQuestion() === CommandQueueStorage::QUESTION_TYPING_AMOUNT) {
                            $amount = $this->sanitizeFloatInput($text);
                            if (is_float($amount)) {
                                $openCommunication->amountConfirm($amount);
                            } else {
                                $openCommunication->amountConfirmFailed($text);
                            }
                        } elseif ($storage->getLastQuestion() === CommandQueueStorage::QUESTION_CONFIRMING_AMOUNT) {
                            $amount = $this->sanitizeFloatInput($text);
                            if (strtoupper($text) === 'BUY') {
                                $openCommunication->buy();
                                $tradingBotCommand->exit(true);
                            } elseif (strtoupper($text) === 'SELL') {
                                $openCommunication->sell();
                                $tradingBotCommand->exit(true);
                            } elseif (is_float($amount)) {
                                $openCommunication->amountConfirm($amount);
                            } else {
                                // %count + + +
                            }
                        }
                    } elseif ($storage->getCommandName() === 'deposit') {
                        $depositCommunication->setup($chatId, $storage, $user);
                        if ($storage->getLastQuestion() === CommandQueueStorage::QUESTION_DEPOSIT) {
                            $depositCommunication->amount($text);
                        } elseif ($storage->getLastQuestion() === CommandQueueStorage::QUESTION_TYPING_USD_AMOUNT) {
                            $depositCommunication->createCryptoPayment($text);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $this->logger->critical('Important Error!');
        }
        

        // MUST ALWAYS BE 200, otherwise webhooks goes in queue ...
        return $this->json([], 200);
    }

    public function handlePaymentWebhook(
        Request $request,
        EventDispatcherInterface $dispatcher
    ): JsonResponse
    {
        # TODO allow requests from only our IP address
        $input = json_decode($request->getContent(), true);
        $dispatcher->dispatch(new HermannPaymentsEvent($input));

        return $this->json([], 200);
    }
}