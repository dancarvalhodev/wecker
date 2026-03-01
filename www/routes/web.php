<?php
declare(strict_types=1);

use App\Controller\UserController;
use Slim\App;
use App\Controller\HomeController;

return function (App $app): void {
    $app->get('/', [HomeController::class, 'index']);
    $app->get('/register', [UserController::class, 'register']);
    $app->post('/register', [UserController::class, 'register']);
    $app->get('/login', [UserController::class, 'login']);
};