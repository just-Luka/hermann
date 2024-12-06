<?php

declare(strict_types=1);

namespace App\State\TradingCommunication\Deposit;

use App\Service\Telegram\Bot\Communication\DepositCommunication;
use App\State\StateInterface;

final readonly class ChoosingDepositState implements StateInterface
{
    public function __construct(private DepositCommunication $depositCommunication) {}

    public function handle(string $input): void
    {
        // TODO rename 'amount' does not match on it purpose!
        $this->depositCommunication->amount($input);
    }
}