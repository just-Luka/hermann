<?php

declare(strict_types=1);

namespace App\EventListener\Webhooks;

use GuzzleHttp\Client;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * TelegramWebhookListener (Alexa)
 */
final class TelegramWebhookListener
{
    private $client;
    private $botToken;

    public function __construct(string $botToken)
    {
        $this->client = new Client();
        $this->botToken = $botToken;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Check if the request is for the Telegram webhook
        if ($request->getPathInfo() === '/telegram-webhook' && $request->isMethod('POST')) {
            $update = json_decode($request->getContent(), true);

            if (isset($update['message'])) {
                $chatId = $update['message']['chat']['id'];
                $text = $update['message']['text'];

                if ($text === '/start') {
                    $responseText = "Welcome! Here are our Telegram channels:\n";
                    $responseText .= "Channel 1: https://t.me/your_channel_1\n";
                    $responseText .= "Channel 2: https://t.me/your_channel_2\n";

                    // Send message back to the user
                    $this->client->post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                        'form_params' => [
                            'chat_id' => $chatId,
                            'text'    => $responseText,
                        ],
                    ]);
                }
            }

            // Stop further propagation of the request (since we've handled it)
            $event->stopPropagation();
        }
    }
}
