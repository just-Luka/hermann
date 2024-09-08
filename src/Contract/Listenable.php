<?php

declare(strict_types=1);

namespace App\Contract;

interface Listenable
{
    public function webhook(): array;
}