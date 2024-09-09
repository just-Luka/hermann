<?php

declare(strict_types=1);

namespace App\Controller\Channels;

use App\Form\TradingSignalType;
use App\Template\TradingSignalTemplate;
use App\Service\Telegram\SignalsTelegramService;
use App\Service\Validation\SimpleValidationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class SignalsController extends AbstractController
{
    private $telegramService;

    public function __construct(SignalsTelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    #[Route('send-signal', methods: ['POST'])]
    public function sendTradingSignal(Request $request, SimpleValidationService $validation): JsonResponse
    {
        $input = $request->request->all();

        if ($validation->fails($request, TradingSignalType::class)) {
            return $this->json([
                'status' => 'error',
                'errors' => $validation->getMessages()
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        $message = sprintf(
            TradingSignalTemplate::default(),
            $input['assetType'],
            $input['assetName'],
            $input['entryPrice'],
            $input['targetPrice'] ?? 'N/A',
            $input['stopPrice'],
            $input['timeFrame'] ?? 'N/A',
            $input['tradeDirection'],
            $input['positionSize'],
            $input['successRate']
        );
    
        $this->telegramService->sendMessage($message);
    
        return $this->json('Message sent!');
    }
}