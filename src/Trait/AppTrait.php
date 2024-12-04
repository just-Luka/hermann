<?php

declare(strict_types=1);

namespace App\Trait;

trait AppTrait
{
    protected function isProd(): bool
    {
        return $_ENV['APP_ENV'] === 'prod';
    }

    protected function url(): string
    {
        return $this->isProd() ? $_ENV['PROD_URL'] : $_ENV['TEST_URL'];
    }

    protected function webhookURL(): string
    {
        return $this->isProd() ? $this->url() : $_ENV['NGROK_URL'];
    }
}
