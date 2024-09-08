<?php

declare(strict_types=1);

namespace App\Trait;

trait AppTrait
{
    public function isProd(): bool
    {
        return $_ENV['APP_ENV'] === 'prod';
    }

    public function url(): string
    {
        return $this->isProd() ? $_ENV['PROD_URL'] : $_ENV['TEST_URL'];
    }
}
