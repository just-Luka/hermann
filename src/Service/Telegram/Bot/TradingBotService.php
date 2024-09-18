<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Contract\Listenable;
use App\Trait\AppTrait;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class TradingBotService implements Listenable
{
    use AppTrait;

    private const WEBHOOK_SLUG = '/webhook_trading';
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
     * Setup Telegram webhook for Trading BOT
     * 
     * @return array
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
        }
    }

    // Not working!
    // public function translationPath(): string
    // {
    //     return __DIR__ . '/../../../../translations/trading/';
    // }
    
    /**
     * isValidTelegramAuth
     * Checks whether Telegram widget auth is valid (by computing hash)
     * 
     * @param  mixed $authData
     * @return bool
     */
    public function isValidTelegramAuth(array $authData): bool
    {
        // Sort the data by key
        ksort($authData);
        
        // Remove the hash parameter
        $checkData = array_filter($authData, fn($key) => $key !== 'hash', ARRAY_FILTER_USE_KEY);
        
        // Create a data string and calculate its hash using HMAC with SHA-256
        $dataString = implode("\n", array_map(fn($key, $value) => $key . '=' . $value, array_keys($checkData), $checkData));
        $secretKey = hash('sha256', $this->token, true);
        $hash = hash_hmac('sha256', $dataString, $secretKey);
        
        // Validate the hash matches the provided one
        return hash_equals($authData['hash'], $hash);
    }

    public function sendMessage(int $chatId, string $message): void
    {
        $url = "https://api.telegram.org/bot{$this->token}/sendMessage";
        $payload = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
        ];

        $this->client->post($url, ['form_params' => $payload]);
    }
}
