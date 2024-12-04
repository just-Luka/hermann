<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\QueuedDeposit;
use Symfony\Contracts\EventDispatcher\Event;

final class DepositEvent extends Event
{
    public function __construct(
        private readonly QueuedDeposit $queuedDeposit,
        private readonly string $addressBase58,
        private readonly ?string $network = null,
        private readonly ?string $coinName = null,
    ) {}

    public function getQueuedDeposit(): QueuedDeposit
    {
        return $this->queuedDeposit;
    }

    public function getAddressBase58(): string
    {
        return $this->addressBase58;
    }

    public function getNetwork(): ?string
    {
        return $this->network;
    }

    public function getCoinName(): ?string
    {
        return $this->coinName;
    }
}
