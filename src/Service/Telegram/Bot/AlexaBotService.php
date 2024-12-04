<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Contract\Listenable;
use App\Contract\Multilingual;
use App\Trait\AppTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

class AlexaBotService implements Listenable, Multilingual
{
    use AppTrait;

    private const WEBHOOK_SLUG = '/webhook_alexa';
    private Client $client;

    public function __construct(
        private readonly string $token,
        private readonly LoggerInterface $logger
    )
    {
        $this->client = new Client();
    }

    /**
     * webhook
     * Setup Telegram webhook for Alexa
     *
     * @return array
     * @throws GuzzleException
     */
    public function webhook(): array
    {
        try {
            $response = $this->client->post("https://api.telegram.org/bot{$this->token}/setWebhook", [
                'form_params' => ['url' => $this->webhookURL() . self::WEBHOOK_SLUG]
            ]);
    
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            $this->logger->error('Failed to set webhook', ['exception' => $e]);
            exit();
        }
    }

    public function translationPath(): string
    {
        return __DIR__ . '/../../../../translations/alexa/';
    }

    public function sendCommandAnswer($chatId, $responseText): void
    {
        try {
            $this->client->post("https://api.telegram.org/bot{$this->token}/sendMessage", [
                'form_params' => [
                    'chat_id' => $chatId,
                    'text'    => $responseText,
                ]
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Error executing command", ['exception' => $e ?? 'IS NULL']);
        }
    }
}
