<?php

declare(strict_types=1);

namespace App\Service\Crypto\Tron;

use App\Contract\Publishable;
use App\Entity\User;
use App\Service\RabbitMQ\RabbitMQClientService;
use App\Trait\CalculationTrait;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;

final class TronAccountService implements Publishable
{
    use CalculationTrait;
    private const EXCHANGE = "tron_events";

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly RabbitMQClientService $mqClient,
    ) {}

    public function __destruct()
    {

    }

    /**
     * @param User $user
     * @param string $amount
     * @return void
     */
    public function requestWalletCreation(User $user, string $amount): void
    {
        $this->publish([
                'type' => 'createWallet',
                'amount' => $amount,
                'user_id' => $user->getId(),
                'timestamp' => time(),
            ],
            'createWallet',
        );
    }
    
    /**
     * Returns address-associated TRC-20 transactions from blockchain 
     *
     * @param  string $addressBase58
     * @return array|null
     */
    public function getTRC20Transactions(string $addressBase58): ?array
    {
        return null;
        // $tronGridApiUrl = "https://api.trongrid.io/v1/accounts/{$addressBase58}/transactions/trc20";

        // $client = new Client();

        // try {
        //     $response = $client->request('GET', $tronGridApiUrl, [
        //         'headers' => [
        //             'Accept' => 'application/json'
        //         ]
        //     ]);

        //     if ($response->getStatusCode() === 200) {
        //         $body = $response->getBody()->getContents();
        //         $transactions = json_decode($body, true);

        //         if (isset($transactions['data']) && count($transactions['data']) > 0) {
        //             return $transactions['data'];
        //         }
        //     }
        // } catch (\Exception $e) {
        //     // TODO CRITICAL ERROR: Deposit can not be checked 
        // }

        // return null;
    }

    public function publish(array $payload, string $routingKey): void
    {
        $connection = $this->mqClient->getConnection();
        $channel = $connection->channel();

        $channel->exchange_declare(self::EXCHANGE, 'direct', false, true, false);
        $message = new AMQPMessage(json_encode($payload), [
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ]);

        try {
            $channel->basic_publish($message, self::EXCHANGE, $routingKey);
            $this->logger->info("createWallet event published.");
        } catch (\Exception $e) {
            $this->logger->warning("Failed to publish createWallet event: " . $e->getMessage());
        } finally {
            $channel->close();
        }
    }
}