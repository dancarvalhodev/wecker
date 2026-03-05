<?php

namespace App\Service;

use App\Entity\User;

class AuthService
{
    public function login(User $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user->getId();
    }

    public function logout(): void
    {
        session_destroy();
    }

    public function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public function getUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }
}