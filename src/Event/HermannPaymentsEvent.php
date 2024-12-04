<?php

declare(strict_types=1);

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class HermannPaymentsEvent extends Event
{
    public function __construct(private readonly array $payload) {}

    public function getPayload(): array
    {
        return $this->payload;
    }
}