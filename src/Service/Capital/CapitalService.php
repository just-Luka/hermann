<?php

declare(strict_types=1);

namespace App\Service\Capital;

use App\Trait\AppTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

class CapitalService
{
    use AppTrait;

    private const REAL_URL = 'https://api-capital.backend-capital.com/api/v1';
    private const DEMO_URL = 'https://demo-api-capital.backend-capital.com/api/v1';

    private $client;
    private $apiKey;
    private $login;
    private $password;
    private $logger;

    public function __construct(string $apiKey, string $login, string $password, LoggerInterface $logger)
    {
        $this->client = new Client();
        $this->apiKey = $apiKey;
        $this->login = $login;
        $this->password = $password;
        $this->logger = $logger;
    }

    public function initSession()
    {
        $url = $this->url() . '/session';

        $headers = [
            'X-CAP-API-KEY' => $this->apiKey,
        ];

        $body = [
            'identifier' => $this->login,
            'password' => $this->password,
            'encryptedPassword' => false,
        ];

        try {
            $response = $this->client->post($url, [
                'headers' => $headers,
                'json' => $body,
            ]);

            $body = json_decode((string) $response->getBody(), true);
            $header = $response->getHeaders();
            
            return [
                'body' => $body,
                'header' => $header,
            ];
        } catch (RequestException $e) {
            $this->logger->error('API request failed: ' . $e->getMessage());

            return null;
        }
    }

    public function url(): string
    {
        return $this->isProd() ? self::REAL_URL : self::DEMO_URL;
    }
}