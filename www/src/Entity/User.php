<?php

namespace App\Entity;

use Carbon\Carbon;
use LogicException;

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
        ?int   $id,
        string $name,
        string $email,
        string $password,
        int    $roleId,
        Carbon $createdAt = new Carbon(),
        Carbon $updatedAt = new Carbon()
    )
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->roleId = $roleId;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function setId(int $id): void
    {
        if ($this->id !== null) {
            throw new LogicException('ID already set');
        }

        $this->id = $id;
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