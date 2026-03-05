<?php

namespace App\Service\User\Validator;

use App\Exception\ValidationException;
use Respect\Validation\ValidatorBuilder as v;
class LoginValidator
{
    /**
     * @param array $data
     * @return void
     * @throws ValidationException
     */
    public function validate(array $data): void
    {
        $errors = [];

        if (!$data) {
            throw new ValidationException(['Login data cannot be empty.']);
        }

        if (v::email()->length(v::between(1, 150))->validate($data['email'] ?? null)->hasFailed()) {
            $errors['email'] = "Invalid email address";
        }

        if (v::stringType()->length(v::between(1, 255))->validate($data['password'] ?? null)->hasFailed()) {
            $errors['password'] = "Password must be between 1 and 255 characters";
        }

        if ($errors) {
            throw new ValidationException($errors);
        }
    }
}