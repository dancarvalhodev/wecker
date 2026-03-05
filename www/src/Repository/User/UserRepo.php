<?php

namespace App\Repository\User;

use App\Entity\Role;
use App\Entity\User;
use App\Repository\AbstractRepo;
use Carbon\Carbon;
use Doctrine\DBAL\Exception;

class UserRepo extends AbstractRepo
{
    /**
     * @param User $user
     * @return User
     * @throws Exception
     */
    public function save(User $user): User
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
            $user->setId($id);

            return $user;
        } catch (Exception $e) {
            $this->connection->rollback();
            error_log($e->__toString());
            throw $e;
        }
    }

    /**
     * @throws Exception
     */
    public function findByEmail($email): ?User
    {
        $data = $this->connection->fetchAssociative(
            'SELECT * FROM users WHERE email = :email',
            ['email' => $email]
        );

        if (!$data) {
            return null;
        }

        return $this->hydrate($data);
    }

    /**
     * @param array $data
     * @return User
     */
    protected function hydrate(array $data): User
    {
        return new User(
            (int)$data['id'],
            $data['name'],
            $data['email'],
            $data['password'],
            Role::ROLE_USER,
            new Carbon($data['created_at']),
            new Carbon($data['updated_at'])
        );
    }
}