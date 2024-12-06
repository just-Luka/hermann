<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot\Communication;

use App\Entity\CommandQueueStorage;
use App\Entity\User;

abstract class BaseCommunication
{
    protected CommandQueueStorage $commandQueueStorage;
    protected User $user;
    protected int $chatId;

    /**
     * @param User $user
     * @param CommandQueueStorage $commandQueueStorage
     * @return $this
     */
    public function setup(User $user, CommandQueueStorage $commandQueueStorage): static
    {
        $this->commandQueueStorage = $commandQueueStorage;
        $this->user = $user;
        $this->chatId = (int) $user->getTelegramChatId();

        return $this;
    }
}