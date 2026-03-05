<?php

namespace App\Model\User;

use App\Entity\Role;
use App\Entity\User;
use App\Exception\UserException;
use App\Exception\ValidationException;
use App\Repository\User\UserRepo;
use Doctrine\DBAL\Exception;

class UserModel
{
    /** @var UserRepo $userRepo */
    private UserRepo $userRepo;

    public function __construct(UserRepo $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    /**
     * @param array $data
     * @return User
     * @throws Exception
     */
    public function createUser(array $data): User
    {
        $user = new User(
            null,
            $data['name'],
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            Role::ROLE_USER,
        );

        $user = $this->userRepo->save($user);

        return $user;
    }

    /**
     * @param array $data
     * @return User
     * @throws Exception
     * @throws UserException
     * @throws ValidationException
     */
    public function authenticateUser(array $data): User
    {
        $user = $this->userRepo->findByEmail($data['email']);

        if (!$user || !password_verify($data['password'], $user->getPassword())) {
            throw new UserException(['Wrong password.']);
        }

        return $user;
    }
}