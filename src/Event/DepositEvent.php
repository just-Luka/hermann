<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\QueuedDeposit;
use Symfony\Contracts\EventDispatcher\Event;

class DepositEvent extends Event
{
    private QueuedDeposit $queuedDeposit;
    private string $addressBase58;
    private ?string $network;
    private ?string $coinName;

    public function __construct(QueuedDeposit $queuedDeposit, string $addressBase58, ?string $network = null, ?string $coinName = null)
    {
        $this->queuedDeposit = $queuedDeposit;
        $this->addressBase58 = $addressBase58;
        $this->network = $network;
        $this->coinName = $coinName;
    }

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
