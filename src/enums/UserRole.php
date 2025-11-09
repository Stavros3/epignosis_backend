<?php

enum UserRole: int
{
    case ADMIN = 1;
    case USER = 2;

    public function getName(): string
    {
        return match($this) {
            self::ADMIN => 'ADMIN',
            self::USER => 'USER',
        };
    }

    public static function isValid(int $value): bool
    {
        return in_array($value, [
            self::ADMIN->value,
            self::USER->value
        ]);
    }

    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }
}
