<?php

declare(strict_types=1);

namespace App\Controller\Bots;

use App\DTO\Bots\TradingBotDTO;
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
        CommandContext $context,
    ): JsonResponse
    {
        try {
            $update = json_decode($request->getContent(), true);

            if (isset($update['message'])) {
                $webhookDTO = new TradingBotDTO($update);

                if ($webhookDTO->getSender()['is_bot']) {
                    return $this->json('Bot communication not supported yet!');
                }

                $tradingBotCommand->setup($webhookDTO->getChatId(), $webhookDTO->getSender());

                $commands = ['start', 'open', 'deposit', 'exit'];
                if (in_array($webhookDTO->getCommand(), $commands)) {
                    $tradingBotCommand->{$webhookDTO->getCommand()}();
                    return $this->json('OK');
                }
    
                // If there is no match for any command, then :
                $text = $webhookDTO->getCommand();
                
                // Check if user is in process of communication.
                // That means he already typed some /command, our bot waits for something!
                $user = $userRepository->findByTelegramId($webhookDTO->getTelegramId());
                $storage = $commandQueueStorage->findOneBy(['user' => $user]);
    
                if ($storage) {
                    $communication = match ($storage->getCommandName()) {
                        'open' => $openCommunication,
                        'deposit' => $depositCommunication,
                    };
                    $communication->setup($user, $storage);

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