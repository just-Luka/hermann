<?php

declare(strict_types=1);

namespace App\State\TradingCommunication\Open;

use App\Service\Telegram\Bot\Communication\OpenCommunication;
use App\State\StateInterface;

final readonly class ChoosingAssetState implements StateInterface
{
    public function __construct(private OpenCommunication $openCommunication) {}

    /**
     * @param string $input
     * @return void
     */
    public function handle(string $input): void
    {
        $assets = $this->openCommunication
            ->getCommandQueueStorage()
            ->getInstructions()['assets'] ?? [];

        if (!is_numeric($input) || (int) $input < 1 || (int) $input > sizeof($assets)) {
            $this->openCommunication->searchAsset($input);
        } else {
            $this->openCommunication->createOrder((int) $input);
        }
    }
}