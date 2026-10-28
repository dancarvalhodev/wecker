<?php

namespace App\Bootstrap;

/**
 * APP_ENV comes from www/.env (loaded by Dotenv into $_ENV) or, in tests, from phpunit.xml.
 */
abstract class App
{
    public static function isProduction(): bool
    {
        return ($_ENV['APP_ENV'] ?? '') === 'production';
    }

    public static function isTesting(): bool
    {
        return ($_ENV['APP_ENV'] ?? '') === 'testing';
    }

    public static function isDevelopment(): bool
    {
        return ($_ENV['APP_ENV'] ?? '') === 'development';
    }
}
