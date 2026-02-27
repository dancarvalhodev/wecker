<?php

namespace App\Entity;

use Carbon\Carbon;

final class User
{
    private ?int $id;

    private string $name;

    private string $email;

    private string $password;

    private int $roleId;

    private Carbon $createdAt;

    private Carbon $updatedAt;

    public function __construct(
        string $name,
        string $email,
        string $password,
        int $roleId,
        Carbon $createdAt,
        Carbon $updatedAt
    ) {
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getRoleId(): int
    {
        return $this->roleId;
    }

    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): Carbon
    {
        return $this->updatedAt;
    }
}