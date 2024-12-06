<?php

namespace App\State\TradingCommunication\Open;

use App\Service\Telegram\Bot\Communication\OpenCommunication;
use App\State\StateInterface;

final readonly class SearchAssetState implements StateInterface
{
    public function __construct(private OpenCommunication $openCommunication) {}

    /**
     * @param string $input
     * @return void
     */
    public function handle(string $input): void
    {
        $this->openCommunication->searchAsset($input);
    }
}