<?php

declare(strict_types=1);

namespace App\Command;

use App\Contract\Listenable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:telegram-bots-webhook-up',
    description: 'Setting up Telegram bot webhooks',
)]
class SetTelegramBotWebhooksCommand extends Command
{
    private $botServices;

    public function __construct(iterable $botServices)
    {
        parent::__construct();
        $this->botServices = $botServices;
    }

    protected function configure(): void
    {
        $this->setDescription('Sets the webhook URL for the Telegram bot.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->botServices as $botService) {
            if ($botService instanceof Listenable) {
                $result = $botService->webhook();
                $output->writeln($result['ok'] ? "Webhook set successfully!" : 'Failed to set webhook: ' . $result['description']);
            }
        }

        return Command::SUCCESS;
    }
}
