<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use GuzzleHttp\Client;

class ErikBotService
{
    private const WEBHOOK_SLUG = '/webhook_erik';
    private Client $client;

    public function __construct(
        private readonly string $token
    )
    {
        $this->client = new Client();
    }
}
