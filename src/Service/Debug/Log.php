<?php

declare(strict_types=1);

namespace App\Service\Debug;

use Psr\Log\LoggerInterface;

/**
 * Log
 * [NICHT BEREIT]
 * TODO: setLogger() nach Bootrstrap aufrufen
 * 
 */
final class Log
{
    private static $logger;

    public static function setLogger(LoggerInterface $logger): void
    {
        self::$logger = $logger;
    }

    public static function emergency(string $message, array $context = []): void
    {
        self::log('emergency', $message, $context);
    }

    public static function alert(string $message, array $context = []): void
    {
        self::log('alert', $message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::log('critical', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('error', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('warning', $message, $context);
    }

    public static function notice(string $message, array $context = []): void
    {
        self::log('notice', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('info', $message, $context);
    }

    public static function debug(string $message, array $context = []): void
    {
        self::log('debug', $message, $context);
    }

    private static function log(string $level, string $message, array $context = []): void
    {
        if (self::$logger) {
            self::$logger->log($level, $message, $context);
        } else {
            throw new \RuntimeException('Logger is not set.');
        }
    }
}
