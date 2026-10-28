<?php

declare(strict_types=1);

use App\Console\Terminal;
use App\Entity\Role;
use App\Model\User\UserModel;
use App\Service\User\Normalizer\GeneralNormalizer;
use DI\Container;

/** @var Container $container */
$container = require __DIR__ . '/bootstrap.php';

$terminal = $container->get(Terminal::class);
$userModel = $container->get(UserModel::class);
$normalizer = $container->get(GeneralNormalizer::class);

if (in_array('--if-missing', $_SERVER['argv'], true) && $userModel->hasAdmin()) {
    $terminal->success('An admin already exists, skipping');
    exit(0);
}

$terminal->line('Create the admin user');

$data = $normalizer->clean([
    'name'     => $terminal->ask('Name: '),
    'email'    => $terminal->ask('Email: '),
    'password' => $terminal->ask('Password: ', hidden: true),
]);

try {
    $admin = $userModel->createUser($data, Role::ROLE_ADMIN);
    $terminal->success("Admin {$admin->getEmail()} created");
} catch (Throwable $e) {
    $terminal->fail($e->getMessage());
}
