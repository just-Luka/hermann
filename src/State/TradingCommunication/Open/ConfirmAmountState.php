<?php

declare(strict_types=1);

namespace App\State\TradingCommunication\Open;

use App\Service\Telegram\Bot\Command\TradingBotCommand;
use App\Service\Telegram\Bot\Communication\OpenCommunication;
use App\State\StateInterface;
use App\Trait\Message\Formatter\MessageFormatterTrait;

final readonly class ConfirmAmountState implements StateInterface
{
    use MessageFormatterTrait;

    public function __construct(
        private OpenCommunication $openCommunication,
        private TradingBotCommand $command
    ) {}

    /**
     * @param string $input
     * @return void
     */
    public function handle(string $input): void
    {
        $amount = $this->sanitizeFloatInput($input);

        if (strtoupper($input) === 'BUY') {
            $this->openCommunication->buy();
            $this->command->exit(true);
        } elseif (strtoupper($input) === 'SELL') {
            $this->openCommunication->sell();
            $this->command->exit(true);
        } elseif (is_float($amount)) {
            $this->openCommunication->amountConfirm($amount);
        } else {
            // %count + + +
        }
    }
}