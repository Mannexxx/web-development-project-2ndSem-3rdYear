<?php

declare(strict_types=1);

namespace App;

final class LoginController
{
    public function __construct(private readonly UserRepository $userRepository)
    {
    }

    public function handleRequest(): array
    {
        $errors = [];
        $success = '';
        $identifier = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $identifier = trim((string) ($_POST['identifier'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');

            if ($identifier === '' || $password === '') {
                $errors[] = 'Please enter your username/email and password.';
            } elseif (!$this->userRepository->authenticate($identifier, $password)) {
                $errors[] = 'Invalid username/email or password.';
            } else {
                $success = 'Login successful.';
            }
        }

        return [
            'errors' => $errors,
            'success' => $success,
            'identifier' => $identifier,
        ];
    }
}
