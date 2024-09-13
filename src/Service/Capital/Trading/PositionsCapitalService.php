<?php

declare(strict_types=1);

namespace App\Service\Capital\Trading;

use App\Service\Capital\CapitalService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

final class PositionsCapitalService
{
    private $capitalService;
    private $logger;
    private $client;
    private $url;

    private $secT = 'cxG41UpRlQU7SHPSrXszY2dEkJeLUla';
    private $cst = 'fi0Te9kbVOd6ND8FMaBY3YTt';
    public function __construct(CapitalService $capitalService, LoggerInterface $logger)
    {
        $this->capitalService = $capitalService;
        $this->logger = $logger;
        $this->client = new Client();
        $this->url = $capitalService->url() . '/positions';
    }

    public function create(): ?array
    {
        // Under maintenance
        //
        // $res = $this->capitalService->initSession();
        // dd($res);
        // return null;
        $payload = [
            'epic' => 'SILVER',
            'direction' => 'SELL',
            'size' => 1,
            'guaranteedStop' => false,
        ];

        $headers = [
            'X-SECURITY-TOKEN' => $this->secT,
            'CST' => $this->cst,
        ];

        try {
            $response = $this->client->post($this->url, [
                'headers' => $headers,
                'json' => $payload,
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

    public function singlePosition($dealId): ?array
    {
        $headers = [
            'X-SECURITY-TOKEN' => $this->secT,
            'CST' => $this->cst,
        ];

        try {
            $url = 'https://demo-api-capital.backend-capital.com/api/v1/confirms/' . $dealId;
            $response = $this->client->get($url, [
                'headers' => $headers,
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
}