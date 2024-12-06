<?php

declare(strict_types=1);

namespace App\Controller\Bots;

use App\Entity\CommandQueueStorage;
use App\Event\HermannPaymentsEvent;
use App\Repository\CommandQueueStorageRepository;
use App\Repository\UserRepository;
use App\Service\Telegram\Bot\Communication\OpenCommunication;
use App\Service\Telegram\Bot\Command\TradingBotCommand;
use App\Service\Telegram\Bot\Communication\DepositCommunication;
use App\Service\Translation\TranslationService;
use App\State\TradingCommunication\Context\CommandContext;
use App\State\TradingCommunication\Deposit\ChoosingDepositState;
use App\State\TradingCommunication\Deposit\TypingUSDAmountState;
use App\State\TradingCommunication\Open\ChoosingAssetState;
use App\State\TradingCommunication\Open\ConfirmAmountState;
use App\State\TradingCommunication\Open\SearchAssetState;
use App\State\TradingCommunication\Open\TypingAmountState;
use App\Trait\Message\Formatter\MessageFormatterTrait;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

final class TradingController extends AbstractController
{
    use MessageFormatterTrait;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly TranslationService $translationService
    ) {}

    public function handleWebhook(
        Request $request, 
        TradingBotCommand $tradingBotCommand, 
        CommandQueueStorageRepository $commandQueueStorage, 
        UserRepository $userRepository, 
        OpenCommunication $openCommunication,
        DepositCommunication $depositCommunication,
    ): JsonResponse
    {
        try {
            $update = json_decode($request->getContent(), true);

            if (isset($update['message'])) {
                $chatId = $update['message']['chat']['id'];
                $command = ltrim($update['message']['text'], '/');
    
                $sender = $update['message']['from'];
                $languageCode = $sender['language_code'];
                $telegramId = $sender['id'];
    
                if ($sender['is_bot']) {
                    return $this->json('Bot communication not supported yet!');
                }
    
                $tradingBotCommand->setup($chatId, $sender);
    
                if ($command === 'start') {
                    $tradingBotCommand->exit(true);
                    $tradingBotCommand->start();
                    return $this->json('OK');
                } elseif($command === 'open') {
                    $tradingBotCommand->exit(true);
                    $tradingBotCommand->open();
                    return $this->json('OK');
                } elseif($command === 'deposit') {
                    $tradingBotCommand->exit(true);
                    $tradingBotCommand->deposit();
                    return $this->json('OK');
                } elseif($command === 'exit') {
                    $tradingBotCommand->exit();
                    return $this->json('OK');
                } 
    
                // If there is no match for any command, then :
                $text = $command;
                
                // Check if user in process of communication.
                // That means he already typed some /command, our bot waits for something!
                $user = $userRepository->findByTelegramId($telegramId);
                $storage = $commandQueueStorage->findOneBy(['user' => $user]);
    
                if ($storage) {
                    $context = new CommandContext();
                    $communication = match ($storage->getCommandName()) {
                        'open' => $openCommunication->setup($user, $storage),
                        'deposit' => $depositCommunication->setup($user, $storage),
                        'default' => null,
                    };

                    switch ($storage->getLastQuestion()) {
                        ### /OPEN
                        case CommandQueueStorage::QUESTION_SEARCH_ASSET:
                            $context->setState(new SearchAssetState($communication));
                            break;
                        case CommandQueueStorage::QUESTION_CHOOSING_ASSET:
                            $context->setState(new ChoosingAssetState($communication));
                            break;
                        case CommandQueueStorage::QUESTION_TYPING_AMOUNT:
                            $context->setState(new TypingAmountState($communication));
                            break;
                        case CommandQueueStorage::QUESTION_CONFIRMING_AMOUNT:
                            $context->setState(new ConfirmAmountState($communication, $tradingBotCommand));
                            break;
                        ### /DEPOSIT
                        case CommandQueueStorage::QUESTION_DEPOSIT:
                            $context->setState(new ChoosingDepositState($communication));
                            break;
                        case CommandQueueStorage::QUESTION_TYPING_USD_AMOUNT:
                            $context->setState(new TypingUSDAmountState($communication));
                            break;
                    }
                    $context->handle($text);
                }
            }
        } catch (Exception $e) {
            $this->logger->critical('Important Error!');
        }
        

        // MUST ALWAYS BE 200, otherwise webhooks goes in queue ...
        return $this->json([], 200);
    }

    public function handlePaymentWebhook(
        Request $request,
        EventDispatcherInterface $dispatcher
    ): JsonResponse
    {
        # TODO allow requests from only our IP address
        $input = json_decode($request->getContent(), true);
        $dispatcher->dispatch(new HermannPaymentsEvent($input));

        return $this->json([], 200);
    }
}