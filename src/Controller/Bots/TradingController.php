<?php

declare(strict_types=1);

namespace App\Controller\Bots;

use App\Entity\CommandQueueStorage;
use App\Repository\CommandQueueStorageRepository;
use App\Repository\UserRepository;
use App\Service\Telegram\Bot\Communication\OpenCommunication;
use App\Service\Telegram\Bot\Command\TradingBotCommand;
use App\Service\Translation\TranslationService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

final class TradingController extends AbstractController
{
    private LoggerInterface $logger;
    private TranslationService $translationService;

    public function __construct(LoggerInterface $logger, TranslationService $translationService)
    {
        $this->logger = $logger;
        $this->translationService = $translationService;
    }
    
    public function handleWebhook(
        Request $request, 
        TradingBotCommand $tradingBotCommand, 
        CommandQueueStorageRepository $commandQueueStorage, 
        UserRepository $userRepository, 
        OpenCommunication $openCommunication,
    ): JsonResponse
    {
        $update = json_decode($request->getContent(), true);

        if (isset($update['message'])) { // Handle when user sends message to bot
            $chatId = $update['message']['chat']['id'];
            $command = ltrim($update['message']['text'], '/');

            $sender = $update['message']['from'];
            $languageCode = $sender['language_code'];
            $telegramId = $sender['id']; // User Telegram ID

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
            } elseif($command === 'exit') {
                $tradingBotCommand->exit();
                return $this->json('OK');
            }

            // If there is no match for any command, then :
            $text = $command;
            
            // Check if user in proccess of communication.
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
                        } elseif (strtoupper($text) === 'SELL') {
                            // Verkaufen
                        } elseif (is_float($amount)) {
                            $openCommunication->amountConfirm($amount);
                        } else {
                            // Etwas anderes
                            // %count + + +
                        }
                    }
                }
            }
        }

        // MUST ALWAYS BE 200, otherwise webhooks goes in queue ...
        return $this->json([], 200);
    }

    private function sanitizeFloatInput(string $input): float|string
    {
        $input = trim($input);
        $normalizedInput = str_replace(',', '.', $input);
        if (is_numeric($normalizedInput)) {
            return floatval($normalizedInput);
        }

        return $input;
    }
}