<?php

declare(strict_types=1);

namespace App\Service\Crypto\Tron;

use IEXBase\TronAPI\Exception\TronException;
use IEXBase\TronAPI\Tron;
use Psr\Log\LoggerInterface;

class TronAccountService
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    
    /**
     * createWallet
     * Creates USDT (Tron) wallet
     * 
     * @return array
     */
    public function createWallet(): ?array
    {
        try {
            $tron = new Tron();
            $generateAddress = $tron->generateAddress();
            $isValid = $tron->isAddress($generateAddress->getAddress());
    
            $this->logger->warning(json_encode($generateAddress->getRawData()));
            return [
                'data' => $generateAddress->getRawData(),
                'isValid' => $isValid,
            ];
        } catch (TronException $e) {
            $this->logger->warning($e->getMessage());
        }
    }
}