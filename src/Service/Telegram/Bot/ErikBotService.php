<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use GuzzleHttp\Client;

class ErikBotService
{
    private const WEBHOOK_SLUG = '/webhook_erik';
    private $client;
    private $token;

    public function __construct(string $token)
    {
        $this->client = new Client();
        $this->token = $token;
    }
}
