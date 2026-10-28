<?php

declare(strict_types=1);

namespace App\Console;

final class Terminal
{
    /**
     * @param string $label
     * @param bool $hidden hides what is typed (for passwords)
     * @return string
     */
    public function ask(string $label, bool $hidden = false): string
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

        return $answer === false ? '' : rtrim($answer, "\r\n");
    }

    /**
     * @param string $message
     * @return void
     */
    public function line(string $message): void
    {
        echo $message . PHP_EOL;
    }

    /**
     * @param string $message
     * @return void
     */
    public function success(string $message): void
    {
        echo "✔ $message" . PHP_EOL;
    }

    /**
     * @param string $message
     * @return never
     */
    public function fail(string $message): never
    {
        fwrite(STDERR, "✖ $message" . PHP_EOL);
        exit(1);
    }
}
