<?php

declare(strict_types=1);

namespace App\Tests\Controller\Bots;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class TradingControllerTest extends WebTestCase
{
    private static array $webhookJsonSample;

    public function __construct()
    {
        parent::__construct();
        static::$webhookJsonSample = [
            "update_id" => 202875508,
            "message" => [
               "message_id" => 1301,
               "from" => [
                  "id" => 7235670713,
                  "is_bot" => false,
                  "first_name" => "Lukas",
                  "username" => "bleibstill",
                  "language_code" => "de"
                ],
               "chat" => [
                  "id" => 7235670713,
                  "first_name" => "Lukas",
                  "username" => "bleibstill",
                  "type" => "private"
               ],
               "date" => 1729366162,
               "text" => "/open",
               "entities" => [
                  [
                     "offset" => 0,
                     "length" => 5,
                     "type" => "bot_command"
                  ]
               ]
            ]
        ];
    }

    public function testGeneralHandleWebhook()
    {
        $client = static::createClient();

        // $tradingBotCommand = $this->createMock(TradingBotCommand::class);
        // $commandQueueStorage = $this->createMock(CommandQueueStorageRepository::class);
        // $userRepository = $this->createMock(UserRepository::class);
        // $openCommunication = $this->createMock(OpenCommunication::class);
        // $depositCommunication = $this->createMock(DepositCommunication::class);
        // $tronAccountService = $this->createMock(TronAccountService::class);

    }
}