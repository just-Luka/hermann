<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\CryptoWallet;
use App\Event\HermannPaymentsEvent;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class HermannPaymentsEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
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
        $this->entityManager->flush();

        $this->logger->info('walletCreated');
        $this->logger->info(json_encode($event->getPayload()));
    }
}