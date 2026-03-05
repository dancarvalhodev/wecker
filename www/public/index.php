<?php
declare(strict_types=1);

use App\Bootstrap\Main;

require __DIR__ . '/../vendor/autoload.php';

session_start();

new Main()->run();