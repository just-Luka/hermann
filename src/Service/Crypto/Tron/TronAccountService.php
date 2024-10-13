<?php

declare(strict_types=1);

namespace App\Service\Crypto\Tron;

use App\Repository\CryptoWalletRepository;
use App\Repository\QueuedDepositRepository;
use App\Repository\UserRepository;
use App\Trait\CalculationTrait;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use IEXBase\TronAPI\Exception\TronException;
use IEXBase\TronAPI\Tron;
use Psr\Log\LoggerInterface;

final class TronAccountService
{
    use CalculationTrait;

    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private CryptoWalletRepository $cryptoWalletRepository;
    private UserRepository $userRepository;
    private QueuedDepositRepository $queuedDepositRepository;

    public function __construct(
        LoggerInterface $logger, 
        EntityManagerInterface $entityManager, 
        CryptoWalletRepository $cryptoWalletRepository, 
        UserRepository $userRepository,
        QueuedDepositRepository $queuedDepositRepository,
    )
    {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->cryptoWalletRepository =  $cryptoWalletRepository;
        $this->userRepository = $userRepository;
        $this->queuedDepositRepository = $queuedDepositRepository;
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
    
            return [
                'data' => $generateAddress->getRawData(),
                'isValid' => $isValid,
            ];
        } catch (TronException $e) {
            // TODO CRITICAL ERROR: New deposit wallet can not be created
            $this->logger->warning($e->getMessage());
        }
    }
    
    /**
     * Returns address-associated TRC-20 transactions from blockchain 
     *
     * @param  string $addressBase58
     * @return array|null
     */
    public function getTRC20Transactions(string $addressBase58): ?array
    {
        $tronGridApiUrl = "https://api.trongrid.io/v1/accounts/{$addressBase58}/transactions/trc20";

        $client = new Client();

        try {
            $response = $client->request('GET', $tronGridApiUrl, [
                'headers' => [
                    'Accept' => 'application/json'
                ]
            ]);

            if ($response->getStatusCode() === 200) {
                $body = $response->getBody()->getContents();
                $transactions = json_decode($body, true);

                if (isset($transactions['data']) && count($transactions['data']) > 0) {
                    return $transactions['data'];
                }
            }
        } catch (\Exception $e) {
            // TODO CRITICAL ERROR: Deposit can not be checked 
        }

        return null;
    }
}