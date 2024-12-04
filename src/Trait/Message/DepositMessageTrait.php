<?php

declare(strict_types=1);

namespace App\Trait\Message;

use App\Entity\CryptoWallet;

trait DepositMessageTrait
{
    public function createCryptoPaymentMessage(array $instructions, CryptoWallet $userWallet): string
    {
        $message = "
<b>To process your Tether (USDT) TRC-20 payment successfully:</b>

- <b>Send the exact USDT amount</b> to the specified address.
- <b>Use only Tron network</b> for your transfer.
- <b>Generate a new payment for</b> each deposit.

==========================
Amount: <b>\${$instructions['amount']}</b>
Address (Tron): <b>{$userWallet->getAddressBase58()}</b>
==========================

Processing takes up to 10-15 minutes.
        ";

        return $message;
    }
}