<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use GuzzleHttp\Client;

class SignalsTelegramService
{
    private string $botToken;
    private string $channelId;
    private Client $client;

    public function __construct(string $botToken, string $channelId)
    {
        $this->botToken = $botToken;
        $this->channelId = $channelId;
        $this->client = new Client();
    }

    public function sendMessage(string $message): void
    {
        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
        $payload = [
            'chat_id' => $this->channelId,
            'text' => $message,
            'parse_mode' => 'HTML',
        ];

        $this->client->post($url, ['form_params' => $payload]);
    }
}