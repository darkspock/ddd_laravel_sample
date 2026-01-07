<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Helpers;

final class EmailHelper
{
    public static function cleanEmail(string $email): string
    {
        $email = strtolower(trim($email));
        if (str_ends_with($email, '.con')) {
            $email = substr_replace($email, '.com', -4);
        }
        return $email;
    }

    public static function isEmail(?string $email): bool
    {
        if (empty($email)) {
            return false;
        }
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

}
