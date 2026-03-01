<?php

namespace App\Service\User\Validator;

use Respect\Validation\ValidatorBuilder;

class FormValidator
{
    public function validate(array $data)
    {
        ValidatorBuilder::email()->assert($data['email']);
        ValidatorBuilder::stringType()->assert($data['name']);
        ValidatorBuilder::stringType()->assert($data['password']);
        ValidatorBuilder::stringType()->assert($data['check-password']);
    }
}