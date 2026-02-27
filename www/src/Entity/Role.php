<?php

namespace App\Entity;

use Carbon\Carbon;

final class Role
{
    private ?int $id;

    private string $name;

    private string $type;

    private Carbon $createdAt;

    public function __construct(string $name, string $type, Carbon $createdAt)
    {
        $this->name = $name;
        $this->type = $type;
        $this->createdAt = $createdAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }
}