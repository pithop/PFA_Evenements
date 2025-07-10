<?php

namespace App\Security;

use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class BcryptPasswordHasher implements PasswordHasherInterface
{
    private PasswordHasherInterface $hasher;

    public function __construct(PasswordHasherFactoryInterface $passwordHasherFactory)
    {
        $this->hasher = $passwordHasherFactory->getPasswordHasher('bcrypt');
    }

    public function hash(string $plainPassword): string
    {
        return $this->hasher->hash($plainPassword);
    }

    public function verify(string $hashedPassword, string $plainPassword): bool
    {
        return $this->hasher->verify($hashedPassword, $plainPassword);
    }

    public function needsRehash(string $hashedPassword): bool
    {
        return $this->hasher->needsRehash($hashedPassword);
    }
}