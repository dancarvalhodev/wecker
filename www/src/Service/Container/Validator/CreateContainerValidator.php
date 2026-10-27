<?php

declare(strict_types=1);

namespace App\Service\Container\Validator;

use App\Exception\ValidationException;
use Respect\Validation\ValidatorBuilder as v;

final class CreateContainerValidator
{
    public const array RESTART_POLICIES = ['no', 'always', 'unless-stopped', 'on-failure'];

    // Image: lowercase words joined by one . _ / : or -, then an optional ":tag".
    // e.g. "nginx", "nginx:alpine", "ghcr.io/org/app:1.2". Two separators in a row ("..") never match,
    // so the image is safe inside the Docker API URL. \z = true end of text (a trailing \n fails).
    private const string IMAGE_PATTERN = '/^[a-z0-9]+([._\/:-][a-z0-9]+)*(:[A-Za-z0-9._-]+)?\z/';

    // Name: starts with a letter or digit, then letters, digits, _ . or - (Docker's own rule).
    private const string NAME_PATTERN = '/^[a-zA-Z0-9][a-zA-Z0-9_.-]*\z/';

    // Port mapping: "host:container", both 1 to 5 digits, e.g. "8081:80".
    private const string PORT_PATTERN = '/^\d{1,5}:\d{1,5}\z/';

    // Env var: KEY=value, the key a letter or _ then letters, digits or _; the value can be anything.
    private const string ENV_PATTERN = '/^[A-Za-z_][A-Za-z0-9_]*=.*\z/';

    /**
     * @param array{image: string, name: string, ports: list<string>, env: list<string>, restart: string} $data
     * @return void
     * @throws ValidationException
     */
    public function validate(array $data): void
    {
        $errors = [];

        if (v::regex(self::IMAGE_PATTERN)->length(v::between(1, 255))->validate($data['image'])->hasFailed()) {
            $errors['image'] = 'Image must look like nginx or nginx:alpine';
        }

        if ($data['name'] !== '' && v::regex(self::NAME_PATTERN)->validate($data['name'])->hasFailed()) {
            $errors['name'] = 'Name may only contain letters, numbers, _ . and -';
        }

        foreach ($data['ports'] as $port) {
            if (v::regex(self::PORT_PATTERN)->validate($port)->hasFailed()) {
                $errors['ports'] = "Invalid port mapping \"$port\", use host:container (8081:80)";
                break;
            }
        }

        foreach ($data['env'] as $line) {
            if (v::regex(self::ENV_PATTERN)->validate($line)->hasFailed()) {
                $errors['env'] = "Invalid variable \"$line\", use KEY=value";
                break;
            }
        }

        if (!in_array($data['restart'], self::RESTART_POLICIES, true)) {
            $errors['restart'] = 'Unknown restart policy';
        }

        if ($errors) {
            throw new ValidationException($errors);
        }
    }
}
