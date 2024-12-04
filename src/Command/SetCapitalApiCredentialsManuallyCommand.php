<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\CapitalSecurity;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:set-capital-api',
    description: 'Setting up capital api credentials manually in db',
)]
final class SetCapitalApiCredentialsManuallyCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    )
    {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Save CST and X-Security tokens to the database.')
            ->addArgument('cst', InputArgument::REQUIRED, 'The CST token')
            ->addArgument('xSecurityToken', InputArgument::REQUIRED, 'The X-Security token');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cst = $input->getArgument('cst');
        $xSecurityToken = $input->getArgument('xSecurityToken');

        $capitalSecurity = (new CapitalSecurity())
            ->setCst($cst)
            ->setXSecurityToken($xSecurityToken)
            ->setCreatedAt(new DateTimeImmutable())
            ->setUpdatedAt(new DateTimeImmutable());

        $this->entityManager->persist($capitalSecurity);
        $this->entityManager->flush();

        $output->writeln('Capital API credentials have been saved.');

        return Command::SUCCESS;
    }
}
