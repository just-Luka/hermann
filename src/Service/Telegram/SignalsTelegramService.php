<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

final readonly class SignalsTelegramService
{
    private Client $client;

    public function __construct(
        private string $botToken,
        private string $channelId
    )
    {
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

        try {
            $this->client->post($url, ['form_params' => $payload]);
        } catch (RequestException $e) {
            throw new \RuntimeException(
                'Failed to send Telegram message: ' . $e->getMessage(),
            );
        }
    }
}