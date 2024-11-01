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
        $this->connection = new AMQPStreamConnection($host, $port, $user, $password);
    }

    public function getConnection(): AMQPStreamConnection
    {
        return $this->connection;
    }
}