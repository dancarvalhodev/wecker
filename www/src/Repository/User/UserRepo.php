<?php

namespace App\Repository\User;

use App\Entity\Role;
use App\Entity\User;
use App\Repository\AbstractRepo;
use Doctrine\DBAL\Exception;

class UserRepo extends AbstractRepo
{
    public function save(User $user)
    {
        try {
            $this->connection->beginTransaction();

            $id = $this->connection->fetchOne(
                'INSERT INTO users (name, email, password, role_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?) RETURNING id',
                [
                    $user->getName(),
                    $user->getEmail(),
                    $user->getPassword(),
                    Role::ROLE_USER,
                    $user->getCreatedAt(),
                    $user->getUpdatedAt()
                ]
            );

            $this->connection->commit();
            return (int) $id;
        } catch (Exception $e) {
            $this->connection->rollback();
            error_log($e->__toString());
        }
    }
}