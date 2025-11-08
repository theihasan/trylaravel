<?php

namespace App\Enums;

enum PostDifficultyLevel: string
{
    case Beginner = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced = 'advanced';

    /**
     * Get all possible values as array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get enum instance from string (fallback to Beginner if invalid).
     */
    public static function fromString(?string $value): self
    {
        $value = strtolower(trim($value ?? ''));

        return match ($value) {
            self::Beginner->value => self::Beginner,
            self::Intermediate->value => self::Intermediate,
            self::Advanced->value => self::Advanced,
            default => self::Beginner,
        };
    }
}
