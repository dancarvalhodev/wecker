<?php

declare(strict_types=1);

use App\Bootstrap\ContainerFactory;
use Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

return ContainerFactory::create();
