<?php

declare(strict_types=1);

namespace App\Controller\Bots;

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
    
    public function handleWebhook(Request $request): JsonResponse
    {
        $update = json_decode($request->getContent(), true);

        return $this->json([], 200);
    }
}