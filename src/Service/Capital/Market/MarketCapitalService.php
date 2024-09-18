<?php

declare(strict_types=1);

namespace App\Service\Capital\Market;

use App\Service\Capital\CapitalService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;

final class MarketCapitalService
{
    private $capitalService;
    private $logger;
    private $client;
    private $url;
    private $cache; // Die Probleme autowiring

    // Constants for Redis keys
    // private const REDIS_SECURITY_TOKEN_KEY = 'security_token';
    // private const REDIS_CST_TOKEN_KEY = 'cst_token';

    // private string $secT;
    // private string $cst;

    public function __construct(CapitalService $capitalService, LoggerInterface $logger, AdapterInterface $cache)
    {
        $this->capitalService = $capitalService;
        $this->logger = $logger;
        $this->client = new Client();
        $this->url = $capitalService->url() . '/markets';
        $this->cache = $cache;

        // $this->secT = $this->cache->getItem(self::REDIS_SECURITY_TOKEN_KEY)->get() ?? 'default_secT';
        // $this->cst = $this->cache->getItem(self::REDIS_CST_TOKEN_KEY)->get() ?? 'default_cst';
    }

    public function pairsSearch($keyword): ?array
    {
        // Define headers
        $headers = [
            'X-SECURITY-TOKEN' => $this->secT,
            'CST' => $this->cst,
        ];
    
        try {
            // Define query parameters
            $queryParams = [
                'searchTerm' => $keyword,
                'epics' => 'SILVER,NATURALGAS',
            ];
    
            // Make the GET request with query parameters
            $response = $this->client->request('GET', $this->url, [
                'headers' => $headers,
                'query' => $queryParams, // Guzzle will append these as query string
            ]);
    
            // Parse the response body
            $body = json_decode($response->getBody()->getContents(), true);
    
            return $body;
        } catch (RequestException $e) {
            if ($e->hasResponse() && $e->getResponse()->getStatusCode() === 401) {
                $session = $this->capitalService->initSession();
                $xtoken = $session['header']['X-SECURITY-TOKEN'][0];
                $cst = $session['header']['CST'][0];

                // Update local tokens
                $this->secT = $xtoken;
                $this->cst = $cst;
                $this->logger->warning('WARNUNG');
                $this->logger->warning($cst);
                $this->logger->warning($xtoken);
                // Store new tokens in Redis
                $this->saveTokenToCache(self::REDIS_SECURITY_TOKEN_KEY, $xtoken);
                $this->saveTokenToCache(self::REDIS_CST_TOKEN_KEY, $cst);
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
        $headers = [
            'X-SECURITY-TOKEN' => $this->secT,
            'CST' => $this->cst,
        ];

        try {
            $url = $this->url . "/{$epic}";
            $response = $this->client->request('GET', $url, [
                'headers' => $headers,
            ]);

            // Parse the response body
            $body = json_decode($response->getBody()->getContents(), true);

            return $body;
        } catch (RequestException $e) {
            if ($e->hasResponse() && $e->getResponse()->getStatusCode() === 401) {
                // $session = $this->capitalService->initSession();
                // $xtoken = $session['header']['X-SECURITY-TOKEN'][0];
                // $cst = $session['header']['CST'][0];

                // // Update local tokens
                // $this->secT = $xtoken;
                // $this->cst = $cst;
                // $this->logger->warning('WARNUN1');
                // $this->logger->warning($cst);
                // $this->logger->warning($xtoken);
                // // Store new tokens in Redis
                // $this->saveTokenToCache(self::REDIS_SECURITY_TOKEN_KEY, $xtoken);
                // $this->saveTokenToCache(self::REDIS_CST_TOKEN_KEY, $cst);
            }
            return null;
        } catch (\Exception $e) {
            // General exception handling
            $this->logger->error('An unexpected error occurred: ' . $e->getMessage());

            return null;
        }
    }

    // private function saveTokenToCache(string $key, string $value): void
    // {
    //     $cacheItem = $this->cache->getItem($key);
    //     $cacheItem->set($value);
    //     $this->cache->save($cacheItem);
    // }
}