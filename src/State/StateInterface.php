<?php

declare(strict_types=1);

namespace App\State;

interface StateInterface
{
    public function handle(string $input): void;
}