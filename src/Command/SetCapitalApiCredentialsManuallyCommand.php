<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\CapitalSecurity;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:set-capital-api',
    description: 'Setting up capital api credentials manually in db',
)]
final class SetCapitalApiCredentialsManuallyCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this->setDescription('');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $capitalSecurity = new CapitalSecurity();
        $capitalSecurity->setCst('7Rm0cdHIt8BAnf5VSSH9xhNj');
        $capitalSecurity->setXSecurityToken('bdw5Mmgs3htvlXeZ3Ao9AgzPd9dE3oP');
        $capitalSecurity->setCreatedAt(new DateTimeImmutable());
        $capitalSecurity->setUpdatedAt(new DateTimeImmutable());

        $this->entityManager->persist($capitalSecurity);
        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
