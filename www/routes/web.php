<?php
declare(strict_types=1);

use Slim\App;
use App\Controller\HomeController;

return function (App $app): void {
    $app->get('/', [HomeController::class, 'index']);
};