<?php

declare(strict_types=1);

namespace App\State\TradingCommunication\Context;

use App\State\StateInterface;

final readonly class CommandContext
{
    private StateInterface $state;

    /**
     * @param StateInterface $state
     * @return void
     */
    public function setState(StateInterface $state): void
    {
        $this->state = $state;
    }

    /**
     * @param string $input
     * @return void
     */
    public function handle(string $input): void
    {
        $this->state->handle($input);
    }
}