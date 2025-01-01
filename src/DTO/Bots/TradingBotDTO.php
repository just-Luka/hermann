<?php

declare(strict_types=1);

namespace App\DTO\Bots;

final readonly class TradingBotDTO
{
    private int $chatId;
    private string $command;
    private array $sender;
    private string $languageCode;
    private int $telegramId;

    public function __construct(array $data)
    {
        $this->chatId = $data['message']['chat']['id'];
        $this->command =  ltrim($data['message']['text'], '/');
        $this->sender = $data['message']['from'];
        $this->languageCode = $data['language_code'];
        $this->telegramId = $data['message']['from']['id'];
    }

    /**
     * @return int
     */
    public function getChatId(): int
    {
        return $this->chatId;
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * @return array
     */
    public function getSender(): array
    {
        return $this->sender;
    }

    /**
     * @return string
     */
    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    /**
     * @return int
     */
    public function getTelegramId(): int
    {
        return $this->telegramId;
    }
}