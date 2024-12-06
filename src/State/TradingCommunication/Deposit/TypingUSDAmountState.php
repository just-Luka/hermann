<?php

declare(strict_types=1);

namespace App\State\TradingCommunication\Deposit;

use App\Service\Telegram\Bot\Communication\DepositCommunication;
use App\State\StateInterface;

final readonly class TypingUSDAmountState implements StateInterface
{
    public function __construct(private DepositCommunication $depositCommunication) {}

    /**
     * @param string $input
     * @return void
     */
    public function handle(string $input): void
    {
        $this->depositCommunication->createCryptoPayment($input);
    }
}