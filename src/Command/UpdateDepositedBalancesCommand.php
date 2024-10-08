<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\CryptoWallet;
use App\Event\DepositEvent;
use App\Repository\QueuedDepositRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsCommand(
    name: 'app:update-deposited-balances',
    description: 'Add a short description for your command',
)]
class UpdateDepositedBalancesCommand extends Command
{
    private QueuedDepositRepository $queuedDepositRepository;
    private EntityManagerInterface $entityManager;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        QueuedDepositRepository $queuedDepositRepository, 
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $eventDispatcher,
    )
    {
        parent::__construct();
        $this->queuedDepositRepository = $queuedDepositRepository;
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    protected function configure(): void
    {
        $this->setDescription('---');
    }

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
