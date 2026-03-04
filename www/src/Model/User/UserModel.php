<?php

namespace App\Model\User;

use App\Entity\Role;
use App\Entity\User;
use App\Repository\User\UserRepo;
use App\Service\User\Normalizer\FormNormalizer;
use App\Service\User\Validator\FormValidator;

class UserModel
{
    /** @var FormValidator $formValidator */
    private FormValidator $formValidator;

    /** @var FormNormalizer $formNormalizer */
    private FormNormalizer $formNormalizer;

    /** @var UserRepo $userRepo */
    private UserRepo $userRepo;

    public function __construct(FormValidator $formValidator, FormNormalizer $formNormalizer, UserRepo $userRepo)
    {
        $this->formValidator = $formValidator;
        $this->formNormalizer = $formNormalizer;
        $this->userRepo = $userRepo;
    }

    public function createUser(array $data): array
    {
        $data = $this->formNormalizer->clean($data);
        $messages = $this->formValidator->validate($data);

        if ($messages) {
            return [
                'success' => false,
                'messages' => $messages
            ];
        }

        $user = new User(
            $data['name'],
            $data['email'],
            $data['password'],
            Role::ROLE_USER,
        );

        // Adjust password to generate hash
        // Adjust timezone of image
        // Sabe only H:i:s to datetimes
        $this->userRepo->save($user);

        return [
            'success' => true
        ];
    }
}