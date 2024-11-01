<?php

declare(strict_types=1);

namespace App\Contract;

interface Publishable
{
    public function publish(array $payload, string $routingKey): void;
}