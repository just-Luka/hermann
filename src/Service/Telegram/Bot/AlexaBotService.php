<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Contract\Listenable;
use App\Trait\AppTrait;
use GuzzleHttp\Client;

class AlexaBotService implements Listenable
{
    use AppTrait;

    private const WEBHOOK_SLUG = '/webhook_alexa';
    private $client;
    private $token;

    public function __construct(string $token)
    {
        $this->client = new Client();
        $this->token = $token;
    }

    public function webhook(): array
    {
        $response = $this->client->post("https://api.telegram.org/bot{$this->token}/setWebhook", [
            'form_params' => ['url' => $this->url().self::WEBHOOK_SLUG]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function handleUpdate(array $update): void
    {
        // $chatId = $update['message']['chat']['id'];
        // $text = $update['message']['text'];

        // if ($text === '/start') {
        //     $responseText = "Welcome! Here are our Telegram channels:\n";
        //     $responseText .= "Channel 1: https://t.me/your_channel_1\n";
        //     $responseText .= "Channel 2: https://t.me/your_channel_2\n";

        //     $this->client->post("https://api.telegram.org/bot{$this->token}/sendMessage", [
        //         'form_params' => [
        //             'chat_id' => $chatId,
        //             'text'    => $responseText,
        //         ]
        //     ]);
        // }
    }
}
