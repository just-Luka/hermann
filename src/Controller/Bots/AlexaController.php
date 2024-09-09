<?php

declare(strict_types=1);

namespace App\Controller\Bots;

use App\Service\Telegram\Bot\AlexaBotService;
use App\Service\Telegram\Bot\Command\PrimaryBC;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use ReflectionClass;
use Symfony\Component\HttpFoundation\JsonResponse;

final class AlexaController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
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

        // HERE
        $this->logger->info('CONTENT');
        $this->logger->notice($request->getContent());
        $this->logger->info('/CONTENT');

        if (isset($update['message'])) {
            $reflection = new ReflectionClass(PrimaryBC::class);

            $chatId = $update['message']['chat']['id'];
            $command = ltrim($update['message']['text'], '/');

            if ($reflection->hasMethod($command)) {
                $method = $reflection->getMethod($command);
    
                if ($method->isStatic()) {
                    try {
                        $responseText = $method->invoke(null);
                        $alexaService->sendCommandAnswer($chatId, $responseText);
    
                        return $this->json('Command executed');
                    } catch (\Exception $e) {
                        $this->logger->error("Error executing command: {$command}", ['exception' => $e]);
    
                        return $this->json('Command execution failed', 500);
                    }
                } else {
                    return $this->json('Command is not static', 400);
                }
            }
        }

        // MUST ALWAYS BE 200
        return $this->json('Command failed', 200);
    }
}