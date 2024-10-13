<?php

declare(strict_types=1);

namespace App\Service\Capital\Market;

use App\Service\Capital\CapitalService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

final class MarketCapitalService
{
    private CapitalService $capitalService;
    private LoggerInterface $logger;
    private Client $client;
    private string $url;

    public function __construct(CapitalService $capitalService, LoggerInterface $logger)
    {
        $this->capitalService = $capitalService;
        $this->logger = $logger;
        $this->client = new Client();
        $this->url = $capitalService->url() . '/markets';
    }

    public function pairsSearch($keyword): ?array
    {
        try {
            // Define query parameters
            $queryParams = [
                'searchTerm' => $keyword,
                'epics' => 'SILVER,NATURALGAS',
            ];
    
            // Make the GET request with query parameters
            $response = $this->client->request('GET', $this->url, [
                'headers' => $this->capitalService->cHeader(),
                'query' => $queryParams, // Guzzle will append these as query string
            ]);
    
            // Parse the response body
            $body = json_decode($response->getBody()->getContents(), true);
    
            return $body;
        } catch (RequestException $e) {
            if ($e->hasResponse() && $e->getResponse()->getStatusCode() === 401) {
                if (! $this->capitalService->getInitAttempt()) { // Ob haben wir init noch nicht versucht
                    $this->capitalService->initSession();
                    $this->capitalService->setInitAttempt(true);
                    return $this->pairsSearch($keyword);
                } else {
                    // Einer Wichtiger Fehler, init aktualisierung FAILED
                }
            }

            return null;
        } catch (\Exception $e) {
            // General exception handling
            $this->logger->error('An unexpected error occurred: ' . $e->getMessage());
    
            return null;
        }
    }
    
    public function singleMarketInfo($epic): ?array
    {
        try {
            $url = $this->url . "/{$epic}";
            $response = $this->client->request('GET', $url, [
                'headers' => $this->capitalService->cHeader(),
            ]);

            // Parse the response body
            $body = json_decode($response->getBody()->getContents(), true);

            return $body;
        } catch (RequestException $e) {
            if ($e->hasResponse() && $e->getResponse()->getStatusCode() === 401) {
                if (! $this->capitalService->getInitAttempt()) { // Ob haben wir init noch nicht versucht
                    $this->capitalService->initSession();
                    $this->capitalService->setInitAttempt(true);
                    return $this->singleMarketInfo($epic);
                } else {
                    // Einer Wichtiger Fehler, init aktualisierung FAILED
                }
            }
            return null;
        } catch (\Exception $e) {
            // General exception handling
            $this->logger->error('An unexpected error occurred: ' . $e->getMessage());

            return null;
        }
    }
}