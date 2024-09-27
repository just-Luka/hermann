<?php

declare(strict_types=1);

namespace App\Service\Capital;

use App\Repository\CapitalSecurityRepository;
use App\Trait\AppTrait;
use Doctrine\ORM\EntityManagerInterface;
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
    private $entityManager;
    private $capitalSecurity;
    private bool $initAttempt = false;

    public function __construct(
        string $apiKey, 
        string $login, 
        string $password, 
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        CapitalSecurityRepository $capitalSecurityRepository
    )
    {
        $this->client = new Client();
        $this->apiKey = $apiKey;
        $this->login = $login;
        $this->password = $password;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->capitalSecurity = $capitalSecurityRepository->findLatest();
    }

    public function getInitAttempt(): bool
    {
        return $this->initAttempt;
    }

    public function setInitAttempt(bool $initAttempt): void
    {
        $this->initAttempt = $initAttempt;
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
            
            $capitalSecurity = $this->capitalSecurity;

            $capitalSecurity->setXSecurityToken($header['X-SECURITY-TOKEN'][0]);
            $capitalSecurity->setCst($header['CST'][0]);

            $this->entityManager->persist($capitalSecurity);
            $this->entityManager->flush();

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

    public function cHeader(): array
    {
        $capitalSecurity = $this->capitalSecurity;

        // Define headers
        $headers = [
            'X-SECURITY-TOKEN' => $capitalSecurity->getXSecurityToken(),
            'CST' => $capitalSecurity->getCst(),
        ];

        return $headers;
    }
}