<?php

declare(strict_types=1);

namespace App\Controller\Bots;

use App\Service\Telegram\Bot\AlexaBotService;
use App\Service\Translation\TranslationService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

final class AlexaController extends AbstractController
{
    private LoggerInterface $logger;
    private TranslationService $translationService;

    public function __construct(LoggerInterface $logger, TranslationService $translationService)
    {
        $this->logger = $logger;
        $this->translationService = $translationService;
    }
    
    /**
     * handleWebhook
     * Will be core for all webhooks, probably move in separte class
     * 
     * @param  Request $request
     * @param  AlexaBotService $alexaService
     * @return JsonResponse
     */
    public function handleWebhook(Request $request, AlexaBotService $alexaService): JsonResponse
    {
        $update = json_decode($request->getContent(), true);

        if (isset($update['message'])) { // Handle when user sends message to bot
            $chatId = $update['message']['chat']['id'];
            $command = ltrim($update['message']['text'], '/');
            $languageCode = $update['message']['from']['language_code'];

            $message = $this->translationService
                ->setLanguage($languageCode)
                ->trans($command);

            if (! $message) { // Command not exists
                return $this->json([], 200);
            }

            try {
                $alexaService->sendCommandAnswer($chatId, $message);

                return $this->json('Command executed');
            } catch (\Exception $e) {
                $this->logger->error("Error executing command: {$command}", ['exception' => $e]);

                return $this->json('Command execution failed', 200);
            }
           
        }

        // MUST ALWAYS BE 200, otherwise webhooks goes in queue ...
        return $this->json([], 200);
    }
}