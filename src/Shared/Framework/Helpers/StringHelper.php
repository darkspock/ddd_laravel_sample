<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Helpers;

use function count;

use Exception;
use Illuminate\Support\Str;

use function in_array;

use RuntimeException;

final class StringHelper
{
    /**
     * Is origin has 2 words must match 2
     * Is origin has 3 words must match 2: Juan Jose Perez will match Juan Perez
     * Is origin has 4 words must match 3: Juan Jose Perez Ruiz will match Juan Perez Ruiz
     * @param  string  $needle
     * @param  string  $hayStack
     * @param  int  $distance
     * @return bool
     */
    public static function isSimilarFullName(string $needle, string $hayStack, int $distance): bool
    {
        $wordsNeedle = explode(' ', $needle);
        $wordsHayStack = explode(' ', $hayStack);
        $found = 0;
        foreach ($wordsNeedle as $wordNeedle) {
            foreach ($wordsHayStack as $wordHayStack) {
                $wordHayStackToSearch =  Str::transliterate(strtolower($wordHayStack));
                $wordNeedleToSearch =  Str::transliterate(strtolower($wordNeedle));
                if ($wordHayStackToSearch && $wordNeedleToSearch && levenshtein($wordNeedleToSearch, $wordHayStackToSearch) <= $distance + 1) {
                    $found++;
                    break;
                }
            }
        }
        $countWordsNeedle = count($wordsNeedle);
        if ($found === 2 && $countWordsNeedle === 3) {
            return true;
        }
        if ($found === 3 && $countWordsNeedle === 4) {
            return true;
        }
        return $found >= $countWordsNeedle;
    }

    public static function isTheSameName(?string $name1, string $name2): bool
    {
        if ($name1 === null) {
            return false;
        }
        return  Str::transliterate(strtolower($name1)) ===
            Str::transliterate(strtolower($name2));
    }

    public static function isTheSameEmail(?string $email1, ?string $email2): bool
    {
        if ($email1 === null || $email2 === null) {
            return false;
        }
        return strtolower($email1) === strtolower($email2);
    }

    public static function isTheSimilarName(?string $name1, string $name2, int $distance = 2): bool
    {
        if ($name1 === null) {
            return false;
        }
        $name1 = Str::transliterate(strtolower($name1));
        $name2 = Str::transliterate(strtolower($name2));
        return $name1 && $name2 && levenshtein($name1, $name2) <= $distance + 1;
    }

    /** @var string[][] */
    public static array $allowedCurrencies = [
        'EUR' => ['EUROS', 'EURO', 'EUR', '€'],
    ];

    public const string MARKDOWN_BREAKLINE = '  ' . PHP_EOL;
    public const string MARKDOWN_LINE_SEPARATOR = '---';

    /**
     * @throws Exception
     */
    public static function getNumberFromString(string $string, bool $throwException = false): float
    {
        if (empty($string)) {
            return 0;
        }
        $string = mb_strtoupper($string, 'UTF-8');
        $string = trim($string);

        $string = str_replace(' ', '', $string);
        $string = self::removeCurrencyFromString($string);

        $coma = strpos($string, ',');

        $point = strpos($string, '.');
        if ($coma && $point) { //1.000,00 or 1,000.00
            if ($coma < $point) { //1,000.00
                $string = str_replace(',', '', $string);

                return (float) $string;
            }
            //1.000,00
            $s = str_replace(['.', ','], ['', '.'], $string);

            return (float) $s;
        }
        if ($coma && ($point === false)) { //1,000 or //10,00
            $ar = explode(',', $string);
            if (count($ar) > 2) { //1,000,0000
                return (float) str_replace(',', '', $string);
            }

            if (strlen($ar[1]) !== 3) { //1,00
                return (float) str_replace(',', '.', $string);
            }

            //1,000 we are really fucked, will treat as dot up to now
            return (float) str_replace(',', '', $string);
        }
        if ($throwException && is_numeric($string) === false) {
            throw new RuntimeException('Not a numeric value');
        }

        return (float) $string;
    }

    public static function removeCurrencyFromString(string $string): string
    {
        $currencies = [];
        foreach (self::$allowedCurrencies as $allowedCurrencyAlternatives) {
            foreach ($allowedCurrencyAlternatives as $currencyAlternative) {
                $currencies[] = $currencyAlternative;
            }
        }

        return str_replace($currencies, '', $string);
    }

    public static function isHTML(string $string): bool
    {
        return $string !== strip_tags($string);
    }

    /**
     * Only for view purposes
     * @param  string  $string
     * @param  int  $length
     * @return string
     */
    public static function truncateWithEllipsis(string $string, int $length): string
    {
        if (mb_strlen($string) > $length) {
            return mb_strimwidth($string, 0, $length, '…');
        }

        return $string;
    }
}
