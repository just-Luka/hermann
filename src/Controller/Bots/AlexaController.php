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
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly TranslationService $translationService
    ) {}

    /**
     * @param Request $request
     * @param AlexaBotService $alexaService
     * @return JsonResponse
     */
    public function handleWebhook(Request $request, AlexaBotService $alexaService): JsonResponse
    {
        $update = json_decode($request->getContent(), true);

        if (isset($update['message'])) {
            $chatId = $update['message']['chat']['id'];
            $command = ltrim($update['message']['text'], '/');
            $languageCode = $update['message']['from']['language_code'];

            $message = $this->translationService
                ->setLanguage($languageCode)
                ->trans($command);

            if (! $message) {
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