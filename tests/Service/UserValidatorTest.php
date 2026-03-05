<?php

namespace App\Tests\Service;

use App\Service\UserValidator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class UserValidatorTest extends TestCase
{
    private UserValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new UserValidator();
    }

    public function testValidUser(): void
    {
        $data = [
            'email' => 'john.doe@university.edu',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'username' => 'johndoe',
            'password' => 'password123',
            'gender' => 'male',
        ];

        $result = $this->validator->validate($data);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testUserWithoutEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Email is required');

        $data = [
            'firstName' => 'John',
            'lastName' => 'Doe',
        ];

        $this->validator->validate($data);
    }

    public function testUserWithInvalidEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Email must be valid');

        $data = [
            'email' => 'not-an-email',
            'firstName' => 'John',
            'lastName' => 'Doe',
        ];

        $this->validator->validate($data);
    }

    public function testUserWithoutFirstName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('First name is required');

        $data = [
            'email' => 'john.doe@university.edu',
            'lastName' => 'Doe',
        ];

        $this->validator->validate($data);
    }

    public function testUserWithoutLastName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Last name is required');

        $data = [
            'email' => 'john.doe@university.edu',
            'firstName' => 'John',
        ];

        $this->validator->validate($data);
    }

    public function testUserWithShortUsername(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Username must be at least 3 characters long');

        $data = [
            'email' => 'john.doe@university.edu',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'username' => 'ab',
        ];

        $this->validator->validate($data);
    }

    public function testUserWithShortPassword(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must be at least 8 characters long');

        $data = [
            'email' => 'john.doe@university.edu',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'password' => '1234567',
        ];

        $this->validator->validate($data);
    }

    public function testUserWithInvalidGender(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Gender must be one of: male, female, other');

        $data = [
            'email' => 'john.doe@university.edu',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'gender' => 'invalid_gender',
        ];

        $this->validator->validate($data);
    }

    public function testUserWithInvalidPhoneNumber(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Phone number must be a valid international format');

        $data = [
            'email' => 'john.doe@university.edu',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'phoneNumber' => 'invalid-phone',
        ];

        $this->validator->validate($data);
    }

    public function testUserWithValidPhoneNumber(): void
    {
        $data = [
            'email' => 'john.doe@university.edu',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'phoneNumber' => '+21612345678',
        ];

        $result = $this->validator->validate($data);

        $this->assertTrue($result['valid']);
    }

    public function testUserWithValidGender(): void
    {
        $data = [
            'email' => 'john.doe@university.edu',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'gender' => 'female',
        ];

        $result = $this->validator->validate($data);

        $this->assertTrue($result['valid']);
    }

    public function testUserWithLongPassword(): void
    {
        $data = [
            'email' => 'john.doe@university.edu',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'password' => 'this_is_a_very_long_password_123',
        ];

        $result = $this->validator->validate($data);

        $this->assertTrue($result['valid']);
    }

    public function testUserWithLongUsername(): void
    {
        $data = [
            'email' => 'john.doe@university.edu',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'username' => 'johndoe123',
        ];

        $result = $this->validator->validate($data);

        $this->assertTrue($result['valid']);
    }
}
