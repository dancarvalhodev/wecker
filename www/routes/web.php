<?php
declare(strict_types=1);

use App\Controller\DashboardController;
use App\Controller\UserController;
use App\Service\AuthMidleware;
use Slim\App;
use App\Controller\HomeController;

return function (App $app): void {
    $app->get('/', [HomeController::class, 'index']);
    $app->get('/register', [UserController::class, 'register']);
    $app->post('/register', [UserController::class, 'register']);
    $app->get('/login', [UserController::class, 'login']);
    $app->post('/login', [UserController::class, 'login']);
    $app->get('/dashboard', [DashboardController::class, 'index'])->add(AuthMidleware::class);
    $app->get('/logout', [UserController::class, 'logout']);
    $app->post('/dashboard/start', [DashboardController::class, 'start'])->add(AuthMidleware::class);
    $app->post('/dashboard/stop', [DashboardController::class, 'stop'])->add(AuthMidleware::class);
    $app->post('/dashboard/list', [DashboardController::class, 'list'])->add(AuthMidleware::class);
};