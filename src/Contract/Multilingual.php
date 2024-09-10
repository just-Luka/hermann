<?php

declare(strict_types=1);

namespace App\Contract;

interface Multilingual
{
    public function translationPath(): string;
}