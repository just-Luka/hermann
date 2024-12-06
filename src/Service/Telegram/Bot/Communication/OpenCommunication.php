<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot\Communication;

use App\Entity\CommandQueueStorage;
use App\Service\Capital\Account\AccountCapitalService;
use App\Service\Capital\Market\MarketCapitalService;
use App\Service\Capital\Trading\PositionsCapitalService;
use App\Service\Telegram\Bot\TradingBotService;
use App\Trait\Message\OpenMessageTrait;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final class OpenCommunication extends BaseCommunication
{
    use OpenMessageTrait;

    private array $limit = [
        'search' => 5,
    ];

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly TradingBotService $tradingBotService,
        private readonly MarketCapitalService $marketCapital,
        private readonly AccountCapitalService $accountCapitalService,
        private readonly PositionsCapitalService $positionsCapitalService,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function getCommandQueueStorage(): CommandQueueStorage
    {
        return $this->commandQueueStorage;
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

            $instructions['assets'][] = $pair;
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

    public function createOrder(int $chosenNumber): void
    {
        $instructions = $this->commandQueueStorage->getInstructions();
        
        // Asset which was $chosen by user
        $instructions['asset'] = $instructions['assets'][$chosenNumber - 1];

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
        if ($minDealSize > $amount || $maxDealSize < $amount) { // TODO Rate checking, reducing
            $message = "
Min available amount is <b>$minDealSize</b>
Max available amount is <b>$maxDealSize</b>
            ";
            $this->tradingBotService->sendMessage($this->chatId, $message);
            return;
        }

        $balance = $this->user->getBalance();
        $minSizeIncrementValue = $pair['dealingRules']['minSizeIncrement']['value'];
        $maxSizeAvailableForUser = $this->maxAssetSizeForUser($balance, $pair['snapshot']['offer'], $pair['instrument']['leverage'], $minSizeIncrementValue);

        if ($amount > $maxSizeAvailableForUser) { // TODO
            $message = "
Balance: <b>$$balance</b>
Margin Balance: <b>$20</b>
P&L: <b>+$140</b>
Assets: <b>1</b>

Not enough balance.
Max available amount is <b>$maxSizeAvailableForUser</b>
            ";

            $this->tradingBotService->sendMessage($this->chatId, $message);
            return;
        }


        $message = $this->amountMessage($balance, $amount, $pair);
        $this->tradingBotService->sendMessage($this->chatId, $message);

        $instructions['size'] = $amount;
        
        $this->commandQueueStorage->setLastQuestion(CommandQueueStorage::QUESTION_CONFIRMING_AMOUNT)
            ->setInstructions($instructions)
            ->setCount(0);

        $this->entityManager->persist($this->commandQueueStorage);
        $this->entityManager->flush();
    }

    public function amountConfirmFailed(string $text)
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
            'epic' => $instructions['asset']['instrument']['epic'],
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

        $confirmation['leverage'] = $instructions['asset']['instrument']['leverage'];
        $confirmation['decimalPlaces'] = $instructions['asset']['snapshot']['decimalPlacesFactor'];
        $message = $this->buyMessage($confirmation, $this->user->getBalance());

        $this->tradingBotService->sendMessage($this->chatId, $message);
    }

    public function sell(): void
    {
        $instructions = $this->commandQueueStorage->getInstructions();

        $payload = [
            'epic' => $instructions['asset']['instrument']['epic'],
            'direction' => 'SELL',
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

        $confirmation['leverage'] = $instructions['asset']['instrument']['leverage'];
        $confirmation['decimalPlaces'] = $instructions['asset']['snapshot']['decimalPlacesFactor'];
        $message = $this->sellMessage($confirmation, $this->user->getBalance());

        $this->tradingBotService->sendMessage($this->chatId, $message);
    }

    public function suspend(): void
    {
        $storage = $this->commandQueueStorage;

        $this->entityManager->remove($storage);
        $this->entityManager->flush();

        $message = "
Exited. ✅
        ";

        $this->tradingBotService->sendMessage($this->chatId, $message);
    }
}

