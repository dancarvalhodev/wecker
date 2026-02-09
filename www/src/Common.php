<?php

namespace App;

abstract class Common
{
    public static function isProduction(): bool
    {
        return getenv('APP_ENV') === 'production';
    }

    public static function isTesting(): bool
    {
        return getenv('APP_ENV') === 'testing';
    }

    public static function isDevelopment(): bool
    {
        return getenv('APP_ENV') === 'development';
    }
}
