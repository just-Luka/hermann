<?php

declare(strict_types=1);

namespace App\Command;

use App\Event\DepositEvent;
use App\Repository\QueuedDepositRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsCommand(
    name: 'app:update-deposited-balances',
    description: 'Update deposited balances',
)]
final class UpdateDepositedBalancesCommand extends Command
{
    public function __construct(
        private readonly QueuedDepositRepository $queuedDepositRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
    )
    {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setDescription('Deposited balances updated to queue!');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \DateMalformedStringException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $now = new \DateTime();
        $twentyMinutesAgo = (clone $now)->modify('-20 minutes');

        $queuedDeposits = $this->queuedDepositRepository->createQueryBuilder('d')
            ->where('d.cron_ignore = :cronIgnore')
            ->andWhere('d.status IN (:statuses)')
            ->setParameter('cronIgnore', false)
            ->setParameter('statuses', ['AWAITING_DEPOSIT', 'AWAITING_CAPITAL'])
            ->getQuery()
            ->getResult();

        foreach ($queuedDeposits as $deposit) {
            if ($deposit->getStatus() === 'AWAITING_DEPOSIT' && $deposit->getCreatedAt() < $twentyMinutesAgo) {
                $deposit->setCronIgnore(true);
                $this->entityManager->persist($deposit);
                continue;
            }

            $cryptoWallet = $deposit->getCryptoWallet();

            $event = new DepositEvent(
                $deposit,
                $cryptoWallet->getAddressBase58(), 
                $cryptoWallet->getNetwork(), 
                $cryptoWallet->getCoinName()
            );
            
            $this->eventDispatcher->dispatch($event);
        }

        $this->entityManager->flush();
        return Command::SUCCESS;
    }
}
