<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Contract\Listenable;
use App\Contract\Multilingual;
use App\Trait\AppTrait;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class AlexaBotService implements Listenable, Multilingual
{
    use AppTrait;

    private const WEBHOOK_SLUG = '/webhook_alexa';
    private $client;
    private $token;
    private $logger;

    public function __construct(string $token, LoggerInterface $logger)
    {
        $this->client = new Client();
        $this->token = $token;
        $this->logger = $logger;
    }
    
    /**
     * webhook
     * Setup Telegram webhook for Alexa
     * 
     * @return array
     */
    public function webhook(): array
    {
        try {
            $response = $this->client->post("https://api.telegram.org/bot{$this->token}/setWebhook", [
                'form_params' => ['url' => $this->webhookURL()]
            ]);
    
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            $this->logger->error('Failed to set webhook', ['exception' => $e]);
        }
    }

    public function translationPath(): string
    {
        return __DIR__ . '/../../../../translations/alexa/';
    }

    /**
     * webhookURL
     * In local we use ngrok to test https webhooks
     * Uses ngrok address if local, otherwise our domain
     * 
     * @return string
     */
    private function webhookURL(): string
    {
        if (! $this->isProd()) {
            return 'https://101c-2a0b-6204-49f7-c500-4d6e-d687-1fe4-13d.ngrok-free.app' . self::WEBHOOK_SLUG;
        }

        return $this->url() . self::WEBHOOK_SLUG;
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
