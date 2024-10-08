<?php

declare(strict_types=1);

namespace App\Service\Capital;

use App\Entity\CapitalAccount;
use App\Repository\CapitalAccountRepository;
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

    private Client $client;
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private CapitalAccountRepository $capitalAccountRepository;
    private CapitalAccount $capitalAccountMain;
    private bool $initAttempt = false;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        CapitalAccountRepository $capitalAccountRepository,
    )
    {
        $this->client = new Client();
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->capitalAccountRepository = $capitalAccountRepository;
        $this->capitalAccountMain = $this->capitalAccountRepository->findOneBy(['is_main' => true]);
    }

    public function getInitAttempt(): bool
    {
        return $this->initAttempt;
    }

    public function setInitAttempt(bool $initAttempt): void
    {
        $this->initAttempt = $initAttempt;
    }

    public function initSession(): ?array
    {
        $url = $this->url() . '/session';

        $account = $this->getCapitalAccountCredentials($this->capitalAccountMain);

        $headers = [
            'X-CAP-API-KEY' => $account['X-CAP-API-KEY'],
        ];

        $body = [
            'identifier' => $account['identifier'],
            'password' => $account['password'],
            'encryptedPassword' => false,
        ];

        try {
            $response = $this->client->post($url, [
                'headers' => $headers,
                'json' => $body,
            ]);

            $body = json_decode((string) $response->getBody(), true);
            $header = $response->getHeaders();

            $capitalAccountMain = $this->capitalAccountMain;
            $capitalAccountMain->setXSecurityToken($header['X-SECURITY-TOKEN'][0]);
            $capitalAccountMain->setCst($header['CST'][0]);

            $this->entityManager->persist($capitalAccountMain);
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
        // Define headers
        $headers = [
            'X-SECURITY-TOKEN' => $this->capitalAccountMain->getXSecurityToken(),
            'CST' => $this->capitalAccountMain->getCst(),
        ];

        return $headers;
    }

    private function getCapitalAccountCredentials(CapitalAccount $capitalAccount): array
    {
        $apiIdentifier = $capitalAccount->getApiIdentifier();

        return [
            'X-CAP-API-KEY' => $_ENV[$apiIdentifier . '_' . 'CAPITAL_API'],
            'identifier' => $_ENV[$apiIdentifier . '_' . 'CAPITAL_LOGIN'],
            'password' => $_ENV[$apiIdentifier . '_' . 'CAPITAL_API_PASS'],
        ];
    }
}