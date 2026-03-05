<?php

namespace App\Service;

use InvalidArgumentException;

class UserValidator
{
    private const MIN_USERNAME_LENGTH = 3;
    private const MIN_PASSWORD_LENGTH = 8;
    private const VALID_GENDERS = ['male', 'female', 'other'];
    private const PHONE_PATTERN = '/^\+?[1-9]\d{1,14}$/';

    public function validate(array $data): array
    {
        $errors = [];

        if (empty($data['email'])) {
            throw new InvalidArgumentException('Email is required');
        }

        if (!$this->isValidEmail($data['email'])) {
            throw new InvalidArgumentException('Email must be valid');
        }

        if (empty($data['firstName'])) {
            throw new InvalidArgumentException('First name is required');
        }

        if (empty($data['lastName'])) {
            throw new InvalidArgumentException('Last name is required');
        }

        if (isset($data['username']) && strlen($data['username']) < self::MIN_USERNAME_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'Username must be at least %d characters long',
                self::MIN_USERNAME_LENGTH
            ));
        }

        if (isset($data['password']) && strlen($data['password']) < self::MIN_PASSWORD_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'Password must be at least %d characters long',
                self::MIN_PASSWORD_LENGTH
            ));
        }

        if (isset($data['gender']) && !in_array($data['gender'], self::VALID_GENDERS, true)) {
            throw new InvalidArgumentException(sprintf(
                'Gender must be one of: %s',
                implode(', ', self::VALID_GENDERS)
            ));
        }

        if (isset($data['phoneNumber']) && !empty($data['phoneNumber'])) {
            if (!$this->isValidPhoneNumber($data['phoneNumber'])) {
                throw new InvalidArgumentException('Phone number must be a valid international format');
            }
        }

        return [
            'valid' => true,
            'errors' => [],
        ];
    }

    private function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function isValidPhoneNumber(string $phone): bool
    {
        return preg_match(self::PHONE_PATTERN, $phone) === 1;
    }
}
