<?php

declare(strict_types=1);

namespace App\Service\RabbitMQ;

use PhpAmqpLib\Connection\AMQPStreamConnection;

final readonly class RabbitMQClientService
{
    private AMQPStreamConnection $connection;

    public function __construct(
        public string $host,
        public int $port,
        public string $user,
        public string $password
    ) {
        try {
            $this->connection = new AMQPStreamConnection($host, $port, $user, $password);
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to establish RabbitMQ connection: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getConnection(): AMQPStreamConnection
    {
        return $this->connection;
    }
}