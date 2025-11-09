<?php

enum VacationStatus: int
{
    case APPROVED = 1;
    case REJECTED = 2;
    case PENDING = 3;

    public function getName(): string
    {
        return match($this) {
            self::APPROVED => 'APPROVED',
            self::REJECTED => 'REJECTED',
            self::PENDING => 'PENDING',
        };
    }

    public static function isValid(int $value): bool
    {
        return in_array($value, [
            self::APPROVED->value,
            self::REJECTED->value,
            self::PENDING->value
        ]);
    }

    public static function getAll(): array
    {
        return [
            self::APPROVED->value => self::APPROVED->getName(),
            self::REJECTED->value => self::REJECTED->getName(),
            self::PENDING->value => self::PENDING->getName(),
        ];
    }
}
