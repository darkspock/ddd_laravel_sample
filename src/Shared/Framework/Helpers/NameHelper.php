<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Helpers;

final readonly class NameHelper
{
    /**
     * @param  string  $value
     * @return array<string>
     */
    public static function explode(string $value): array
    {
        if ($value === '') {
            return ['.', '.'];
        }
        $words = explode(' ', $value);

        if (count($words) === 1) {
            return [$value, '.'];
        }
        if (count($words) === 2) {
            return $words;
        }
        // 3 words or more. 2 first words goes to name and the rest is the last name
        $firstName = array_shift($words);
        $firstName .= ' ' . array_shift($words);
        $lastName = implode(' ', $words);
        return [$firstName, $lastName];
    }

}
