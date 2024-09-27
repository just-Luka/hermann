<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot\Communication;

use App\Entity\CommandQueueStorage;
use App\Entity\User;
use App\Service\Capital\Account\AccountCapitalService;
use App\Service\Capital\Market\MarketCapitalService;
use App\Service\Capital\Trading\PositionsCapitalService;
use App\Service\Telegram\Bot\TradingBotService;
use App\Trait\Message\OpenMessageTrait;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final class OpenCommunication
{
    use OpenMessageTrait;

    private $logger;
    private $tradingBotService;
    private $chatId;
    private $marketCapital;
    private $accountCapitalService;
    private $positionsCapitalService;
    private $entityManager;
    private $commandQueueStorage;
    private $user;

    private array $limit = [
        'search' => 5,
    ];
    public function __construct(
        LoggerInterface $logger, 
        TradingBotService $tradingBotService,
        MarketCapitalService $marketCapital,
        AccountCapitalService $accountCapitalService,
        PositionsCapitalService $positionsCapitalService,
        EntityManagerInterface $entityManager,
    )
    {
        $this->logger = $logger;
        $this->tradingBotService = $tradingBotService;
        $this->marketCapital = $marketCapital;
        $this->accountCapitalService = $accountCapitalService;
        $this->positionsCapitalService = $positionsCapitalService;
        $this->entityManager = $entityManager;
    }

    public function setup(int $chatId, CommandQueueStorage $commandQueueStorage, User $user): void
    {
        $this->chatId = $chatId;
        $this->commandQueueStorage = $commandQueueStorage;
        $this->user = $user;
    }

    public function searchAsset(string $text): void
    {
        $instructions = $this->commandQueueStorage->getInstructions();

        $count = $this->commandQueueStorage->getCount();
        if ($count >= $this->limit['search']) {
            $this->suspend();
            return;
        }

        $pairs = $this->marketCapital->pairsSearch($text);
        if (is_null($pairs)) {
            $this->logger->critical('Connection with capital was lost searchAsset');
            return;
        }

        $instructions['assets'] = [];
        foreach ($pairs['markets'] as $pair) {
            if ($pair['marketStatus'] !== 'TRADEABLE' || $pair['expiry'] !== '-') {
                continue;
            }

            array_push($instructions['assets'], $pair);
        }

        $message = $this->searchMessage($instructions);
        $this->tradingBotService->sendMessage($this->chatId, $message);

        $this->commandQueueStorage->setLastQuestion(CommandQueueStorage::QUESTION_CHOOSING_ASSET);
        $this->commandQueueStorage->setInstructions($instructions);
        
        // Check if $text is not a number or is not within the range 1-20
        if (!is_numeric($text) || (int)$text < 1 || (int)$text > 20) { 
            $this->commandQueueStorage->setCount($count + 1);
        }

        $this->entityManager->persist($this->commandQueueStorage);
        $this->entityManager->flush();
    }

    public function createOrder(int $choosenNumber): void
    {
        $instructions = $this->commandQueueStorage->getInstructions();
        
        // Asset which was choosen by user
        $instructions['asset'] = $instructions['assets'][$choosenNumber - 1]; 

        $pair = $this->marketCapital->singleMarketInfo($instructions['asset']['epic']);
        if (is_null($pair)) {
            $this->logger->critical('Connection with capital was lost createOrder');
            return;
        }
        $pair['instrument']['leverage'] = $this->accountCapitalService->leverage[$pair['instrument']['type']];
        
        $message = $this->createMessage($pair, $this->user->getBalance());
        $this->tradingBotService->sendMessage($this->chatId, $message);

        $instructions['asset'] = $pair; # instructions 'asset' = $pair
        $this->commandQueueStorage->setLastQuestion(CommandQueueStorage::QUESTION_TYPING_AMOUNT);
        $this->commandQueueStorage->setInstructions($instructions);

        $this->entityManager->persist($this->commandQueueStorage);
        $this->entityManager->flush();
    }

    public function amountConfirm(float $amount): void
    {
        $instructions = $this->commandQueueStorage->getInstructions();

        $pair = $this->marketCapital->singleMarketInfo($instructions['asset']['instrument']['epic']);
        
        if (is_null($pair)) {
            $this->logger->critical('Connection with capital was lost AmountConfirm');
            return;
        }

        $pair['instrument']['leverage'] = $this->accountCapitalService->leverage[$pair['instrument']['type']];

        $minDealSize = $pair['dealingRules']['minDealSize']['value'];
        $maxDealSize = $pair['dealingRules']['maxDealSize']['value'];
        if ($minDealSize > $amount || $maxDealSize < $amount) {
            // Wrong amount
            // min amount :
            // max amount :
            // return;
        }

        $balance = $this->user->getBalance();
        $minSizeIncrementValue = $pair['dealingRules']['minSizeIncrement']['value'];
        $maxSizeAvailableForUser = $this->maxAssetSizeForUser($balance, $pair['snapshot']['offer'], $pair['instrument']['leverage'], $minSizeIncrementValue);

        if ($amount > $maxSizeAvailableForUser) {
            // Not enough funds
            // max amount: 12000
            // return;
        }


        $message = $this->amountMessage($balance, $amount, $pair);
        $this->tradingBotService->sendMessage($this->chatId, $message);

        $instructions['size'] = $amount;
        
        $this->commandQueueStorage->setLastQuestion(CommandQueueStorage::QUESTION_CONFIRMING_AMOUNT);
        $this->commandQueueStorage->setInstructions($instructions);
        $this->commandQueueStorage->setCount(0);

        $this->entityManager->persist($this->commandQueueStorage);
        $this->entityManager->flush();
    }

    public function amountConfirmFailed($text)
    {
        $count = $this->commandQueueStorage->getCount();

        if ($count > 3) {
            // Command /exit
        }

        $message = 
"
<b>$text</b> is not a valid number, please try it again.
";

        $this->tradingBotService->sendMessage($this->chatId, $message);

        $this->commandQueueStorage->setCount($count + 1);

        $this->entityManager->persist($this->commandQueueStorage);
        $this->entityManager->flush();
    }

    // If Telegram webhook fails, capital opens positions for each request FIX IT
    public function buy(): void
    {
        $instructions = $this->commandQueueStorage->getInstructions();

        $payload = [
            'epic' => $instructions['asset']['epic'],
            'direction' => 'BUY',
            'size' => $instructions['size'],
            'guaranteedStop' => false,
        ];

        $order = $this->positionsCapitalService->create($payload);
        if (is_null($order)) {
            $this->logger->critical('Connection with capital was lost BUY');
            return;
        }

        $confirmation = $this->positionsCapitalService->confirm($order['body']['dealReference']);
        if (is_null($confirmation)) {
            $this->logger->critical('Connection with capital was lost confirmation');
            return;
        }

        if ($confirmation['dealStatus'] !== 'ACCEPTED') {
            // something went off
        }

        $dealReference = $order['body']['dealReference'];
        $balance = $this->user->getBalance();
        $leverage = $this->accountCapitalService->leverage[$instructions['asset']['instrumentType']];
        $initialMargin = $balance / $leverage;
        $positionValue = $confirmation['level'] * $confirmation['size'];
        $securingMargin = 0.9; // with 10% margin position will be closed

        $liqPriceLong = ($positionValue - ($balance * $securingMargin - $initialMargin)) / $confirmation['size'];
        $liqPriceLongFormatted = ($liqPriceLong < 0.00000001) ? "0" : number_format($liqPriceLong, $instructions['asset']['snapshot']['decimalPlacesFactor']);
        $entryPriceFormatted = number_format($confirmation['level'], $instructions['asset']['snapshot']['decimalPlacesFactor']);
        $balanceFormatted =  number_format($balance, 2);
        $marginBalanceFormatted = ""; // TODO

        $message = "
Your order was accepted ✅
---------------------------------------
<b>{$confirmation['epic']}</b>
Entry price: {$entryPriceFormatted}
Size: {$confirmation['size']}
Direction: BUY
DR code: $dealReference

---------------------------------------
Balance: $$balanceFormatted
Margin Balance: $100
Estimated Liq. Price: $liqPriceLongFormatted
";
        $this->tradingBotService->sendMessage($this->chatId, $message);

    }

    public function sell(): void
    {
        // pass 
    }

    public function suspend(): void
    {
        $storage = $this->commandQueueStorage;

        if ($storage) {
            $this->entityManager->remove($storage);
            $this->entityManager->flush();
        }

        $message = "
Exited. ✅
        ";

        $this->tradingBotService->sendMessage($this->chatId, $message);
    }
}

