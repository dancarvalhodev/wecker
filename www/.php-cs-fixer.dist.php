<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in([
        __DIR__ . '/bin',
        __DIR__ . '/bootstrap',
        __DIR__ . '/config',
        __DIR__ . '/public',
        __DIR__ . '/routes',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->name('*.php');

return new Config()
    ->setRules([
        '@PER-CS2.0' => true,
    ])
    ->setCacheFile(__DIR__ . '/storage/cache/php-cs-fixer.cache')
    ->setFinder($finder);
