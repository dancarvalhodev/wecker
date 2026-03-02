<?php

namespace App\Service\User\Validator;

use Respect\Validation\ValidatorBuilder as v;
class FormValidator
{
    /**
     * @param array $data
     * @return array
     */
    public function validate(array $data): array
    {
        $errors = [];

        if (v::email()->length(v::between(1, 150))->validate($data['email'] ?? null)->hasFailed()) {
            $errors['email'] = "Invalid email address (max 150 chars)";
        }

        if (v::stringType()->length(v::between(1, 200))->validate($data['name'] ?? null)->hasFailed()) {
            $errors['name'] = "Name must be between 1 and 200 characters";
        }

        if (v::stringType()->length(v::between(1, 255))->validate($data['password'] ?? null)->hasFailed()) {
            $errors['password'] = "Password must be between 1 and 255 characters";
        }

        if (v::stringType()->length(v::between(1, 255))->validate($data['check-password'] ?? null)->hasFailed()) {
            $errors['check-password'] = "Check password must be between 1 and 255 characters";
        }

        if (($data['password'] ?? '') !== ($data['check-password'] ?? '')) {
            $errors['check-password'] = "Passwords do not match";
        }

        return $errors;
    }
}