<?php

declare(strict_types=1);

namespace App\State\TradingCommunication\Open;

use App\Service\Telegram\Bot\Communication\OpenCommunication;
use App\State\StateInterface;
use App\Trait\Message\Formatter\MessageFormatterTrait;

final readonly class TypingAmountState implements StateInterface
{
    use MessageFormatterTrait;

    public function __construct(private OpenCommunication $openCommunication) {}

    /**
     * @param string $input
     * @return void
     */
    public function handle(string $input): void
    {
        $amount = $this->sanitizeFloatInput($input);

        if (is_float($amount)) {
            $this->openCommunication->amountConfirm($amount);
        } else {
            $this->openCommunication->amountConfirmFailed($input);
        }
    }
}