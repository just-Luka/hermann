<?php

declare(strict_types=1);

namespace App\Service\Capital\Trading;

use App\Service\Capital\CapitalService;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

final class PositionsCapitalService
{
    private CapitalService $capitalService;
    private LoggerInterface $logger;
    private Client $client;
    private string $url;
    private string $confirmURL;

    public function __construct(CapitalService $capitalService, LoggerInterface $logger)
    {
        $this->capitalService = $capitalService;
        $this->logger = $logger;
        $this->client = new Client();
        $this->url = $capitalService->url() . '/positions';
        $this->confirmURL = $capitalService->url() . '/confirms';
    }

    public function create(array $payload): ?array
    {
        try {
            $response = $this->client->post($this->url, [
                'headers' => $this->capitalService->cHeader(),
                'json' => $payload,
            ]);

            $body = json_decode((string) $response->getBody(), true);
            $header = $response->getHeaders();
            
            return [
                'body' => $body,
                'header' => $header,
            ];
        } catch (RequestException $e) {
            if ($e->hasResponse() && $e->getResponse()->getStatusCode() === 401) {
                if (! $this->capitalService->getInitAttempt()) { // Ob haben wir init noch nicht versucht
                    $this->capitalService->initSession();
                    $this->capitalService->setInitAttempt(true);
                    return $this->create($payload);
                } else {
                    // Einer Wichtiger Fehler, init aktualisierung FAILED
                }
            }
            return null;
        } catch (Exception $e) {
            $this->logger->error('API request failed: ' . $e->getMessage());

            return null;
        }
    }

    public function confirm($dealId): ?array
    {
        try {
            $response = $this->client->get($this->confirmURL . "/$dealId", [
                'headers' => $this->capitalService->cHeader(),
            ]);

            $body = json_decode((string) $response->getBody(), true);
            
            return $body;
        } catch (RequestException $e) {
            if ($e->hasResponse() && $e->getResponse()->getStatusCode() === 401) {
                if (! $this->capitalService->getInitAttempt()) { // Ob haben wir init noch nicht versucht
                    $this->capitalService->initSession();
                    $this->capitalService->setInitAttempt(true);
                    return $this->confirm($dealId);
                } else {
                    // Einer Wichtiger Fehler, init aktualisierung FAILED
                }
            }
            return null;
        } catch (Exception $e) {
            $this->logger->error('API request failed: ' . $e->getMessage());

            return null;
        }
    }
}