<?php

declare(strict_types=1);

namespace App\Trait\Message;

trait DepositMessageTrait
{
    protected function createCryptoPaymentMessage(string $amount, string $addressBase58): string
    {
        $message = "
<b>To process your Tether (USDT) TRC-20 payment successfully:</b>

- <b>Send the exact USDT amount</b> to the specified address.
- <b>Use only Tron network</b> for your transfer.
- <b>Generate a new payment for</b> each deposit.

==========================
Amount: <b>\${$amount}</b>
Address (Tron): <b>{$addressBase58}</b>
==========================

Processing takes up to 10-15 minutes.
        ";

        return $message;
    }
}