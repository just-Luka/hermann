<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot\Communication;

use App\Entity\CommandQueueStorage;
use App\Service\Capital\Account\AccountCapitalService;
use App\Service\Capital\Market\MarketCapitalService;
use App\Service\Telegram\Bot\TradingBotService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final class OpenCommunication
{
    private $logger;
    private $tradingBotService;
    private $chatId;
    private $marketCapital;
    private $accountCapitalService;
    private $entityManager;
    private $commandQueueStorage;

    public function __construct(
        LoggerInterface $logger, 
        TradingBotService $tradingBotService,
        MarketCapitalService $marketCapital,
        AccountCapitalService $accountCapitalService,
        EntityManagerInterface $entityManager,
    )
    {
        $this->logger = $logger;
        $this->tradingBotService = $tradingBotService;
        $this->marketCapital = $marketCapital;
        $this->accountCapitalService = $accountCapitalService;
        $this->entityManager = $entityManager;
    }

    public function setup(int $chatId, CommandQueueStorage $commandQueueStorage): void
    {
        $this->chatId = $chatId;
        $this->commandQueueStorage = $commandQueueStorage;
    }

    public function searchAsset($text): void
    {
        $pairs = $this->marketCapital->pairsSearch($text);
        if (is_null($pairs)) {
            $this->logger->critical('Connection with capital was lost');
            return;
        }

        $pairs = $pairs['markets'];
        $filteredPairs = [];

        foreach ($pairs as $pair) {
            if ($pair['marketStatus'] !== 'TRADEABLE' || $pair['expiry'] !== '-') {
                continue;
            }

            array_push($filteredPairs, $pair);
        }

        $pairsCount = count($filteredPairs);

        // Start the message with the asset count
        $message = "Found $pairsCount assets:\n\n";

        // Loop through filtered pairs to build the asset list dynamically
        foreach ($filteredPairs as $index => $pair) {
            $number = $index + 1;
            $symbol = $pair['symbol']; // Assuming 'symbol' is a field in $pair
            $marketType = $pair['instrumentType']; // Assuming 'marketType' defines the type (Crypto, Shares, etc.)
            $instrumentName = $pair['instrumentName']; // Assuming 'askPrice' is a field in $pair
            $askPrice = $pair['offer']; // Assuming 'askPrice' is a field in $pair
            $bidPrice = $pair['bid']; // Assuming 'bidPrice' is a field in $pair
            
            $message .= "$number. <b>$instrumentName</b> ($marketType)\n";
            $message .= "------------------------------------------------\n";
            $message .= "Ask price: <b>$askPrice</b>\n";
            $message .= "Bid price: <b>$bidPrice</b>\n";
            $message .= "------------------------------------------------\n\n";
        }

        $message .= $pairsCount > 0 ? "Please choose a number\n\nor type /exit to cancel" : "Please try again\n\nor type /exit to cancel";
        
        $this->tradingBotService->sendMessage($this->chatId, $message);
        ///////////////////////////////

        $this->commandQueueStorage->setLastQuestion(CommandQueueStorage::QUESTION_CHOOSING_ASSET);

        $instructions = $this->commandQueueStorage->getInstructions();
        $instructions['target_epic'] = $filteredPairs;
        $this->commandQueueStorage->setInstructions($instructions);

        if (!is_numeric($text) || (int)$text < 1 || (int)$text > 20) { // Check if $text is not a number or is not within the range 1-20
            $prevCount = $this->commandQueueStorage->getCount();
            $this->commandQueueStorage->setCount($prevCount + 1);
        }

        $this->entityManager->persist($this->commandQueueStorage);
        $this->entityManager->flush();
    }

    public function createOrder(int $choosenNumber): void
    {
        $instructions = $this->commandQueueStorage->getInstructions();
        // Asset which was choosen by user
        $asset = $instructions['target_epic'][$choosenNumber - 1];

        $pair = $this->marketCapital->singleMarketInfo($asset['epic']);
        if (is_null($pair)) {
            $this->logger->critical('Connection with capital was lost');
            return;
        }

        $instrumentName = $pair['instrument']['name'];
        $symbol = $pair['instrument']['symbol'];
        $overnightFeeLong = $pair['instrument']['overnightFee']['longRate'];
        $overnightFeeShort = $pair['instrument']['overnightFee']['shortRate'];
        $minDealSize = $pair['dealingRules']['minDealSize']['value'];
        $maxDealSize = $pair['dealingRules']['maxDealSize']['value'];
        $askPrice = $pair['snapshot']['offer'];
        $bidPrice = $pair['snapshot']['bid'];
        $leverage = $this->accountCapitalService->leverage[$pair['instrument']['type']];

        $message = 
"
<b>$instrumentName</b>
<b>($symbol)</b>

------------------------------------------------
Ask Price: $askPrice
Bid Price: $bidPrice
------------------------------------------------
Overnight Fee (Long): $overnightFeeLong%
Overnight Fee (Short): $overnightFeeShort%
------------------------------------------------
Deal Size (Min): $minDealSize
Deal Size (Max): $maxDealSize
------------------------------------------------
Leverage: $leverage:1
";
    
        // Send the message via Telegram
        $this->tradingBotService->sendMessage($this->chatId, $message);
    }
}

