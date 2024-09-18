<?php

declare(strict_types=1);

namespace App\Service\Capital\Account;

final class AccountCapitalService
{
    public array $leverage = [
        'CURRENCY' => 100,
        'INDICES' => 100,
        'COMMODITIES' => 100,
        'SHARES' => 20,
        'CRYPTO' => 20,
    ];

    public function preferences()
    {
        // Account preferences
    }
}