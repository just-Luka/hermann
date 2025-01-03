<?php

declare(strict_types=1);

namespace App\State\TradingCommunication\Context;

use App\State\StateInterface;

final readonly class CommandContext
{
    private ?StateInterface $state;

    /**
     * @param StateInterface|null $state
     * @return CommandContext
     */
    public function setState(?StateInterface $state): self
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @param string $input
     * @return void
     */
    public function handle(string $input): void
    {
        if (isset($this->state)) {
            $this->state->handle($input);
        }
    }
}