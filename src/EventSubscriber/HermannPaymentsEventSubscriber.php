<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\CryptoWallet;
use App\Entity\QueuedDeposit;
use App\Event\HermannPaymentsEvent;
use App\Repository\UserRepository;
use App\Service\Telegram\Bot\TradingBotService;
use App\Trait\Message\DepositMessageTrait;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class HermannPaymentsEventSubscriber implements EventSubscriberInterface
{
    use DepositMessageTrait;

    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private TradingBotService $tradingBotService,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            HermannPaymentsEvent::class => [
                ['walletCreated', 10],
                // Anderen
            ],
        ];
    }

    public function walletCreated(HermannPaymentsEvent $event): void
    {
        $payload = $event->getPayload();
        if ($payload['type'] !== __FUNCTION__) return;

        $user = $this->userRepository->findOneBy(['id' => $payload['user_id']]);

        try {
            $this->entityManager->beginTransaction();

            $cryptoWallet = (new CryptoWallet())
                ->setUser($user)
                ->setCreatedAt(new \DateTimeImmutable())
                ->setUpdatedAt(new \DateTimeImmutable())
                ->setCoinName(CryptoWallet::COIN_NAME_USDT) # TODO (dynamically set other coins)
                ->setNetwork(CryptoWallet::NETWORK_TRC20) # TODO (dynamically set other nets)
                ->setAddressBase58($payload['account']['address']['base58'])
                ->setAddressHex($payload['account']['address']['hex'])
                ->setPrivateKey($payload['account']['privateKey'])
                ->setPublicKey($payload['account']['publicKey']);
            $this->entityManager->persist($cryptoWallet);

            $queuedDeposit = (new QueuedDeposit())
                ->setCryptoWallet($cryptoWallet)
                ->setCreatedAt(new \DateTimeImmutable())
                ->setUpdatedAt(new \DateTimeImmutable())
                ->setAmount($payload['amount']);

            $this->entityManager->persist($queuedDeposit);

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $exception) {
            $this->entityManager->rollback();
            $this->tradingBotService->sendMessage((int) $user->getTelegramChatId(), 'Something went wrong, try again later.');
            return;
        }

        $this->tradingBotService->sendMessage((int) $user->getTelegramChatId(), $this->createCryptoPaymentMessage($payload['amount'], $cryptoWallet->getAddressBase58()));
    }
}