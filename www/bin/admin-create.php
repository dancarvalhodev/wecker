<?php

declare(strict_types=1);

use App\Entity\Role;

require __DIR__ . '/bootstrap.php';

$connection = connectTo($_ENV['DB_NAME']);

if (in_array('--if-missing', $_SERVER['argv'], true)) {
    $admins = (int) $connection->fetchOne('SELECT COUNT(*) FROM users WHERE role_id = ?', [Role::ROLE_ADMIN]);

    if ($admins > 0) {
        echo '✔ An admin already exists, skipping' . PHP_EOL;
        exit(0);
    }
}

echo 'Create the admin user' . PHP_EOL;

$name = ask('Name: ');
$email = strtolower(ask('Email: '));
$password = ask('Password: ', hidden: true);

$connection->insert('users', [
    'name'     => $name,
    'email'    => $email,
    'password' => password_hash($password, PASSWORD_DEFAULT),
    'role_id'  => Role::ROLE_ADMIN,
]);

echo "✔ Admin $email created" . PHP_EOL;

/**
 * @param string $label
 * @param bool $hidden
 * @return string
 */
function ask(string $label, bool $hidden = false): string
{
    echo $label;

    if ($hidden) {
        shell_exec('stty -echo 2>/dev/null');
    }

    $answer = fgets(STDIN);

    if ($hidden) {
        shell_exec('stty echo 2>/dev/null');
        echo PHP_EOL;
    }

    return trim($answer === false ? '' : $answer);
}
