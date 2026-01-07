<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Helpers;

use InvalidArgumentException;

final readonly class NumberHelper
{
    public static function equals(float $a, float $b, float $delta = 0.00001): bool
    {
        $diff = abs($a - $b);

        return $diff < $delta;
    }


    /**
     * Returns the diff percentage between two numbers. But if do not overpass the minDiff or minPercent will return false.
     * @param  float  $a
     * @param  float  $b
     * @param  float  $minDiff
     * @param  float  $minPercent
     * @return false|float
     */
    public static function percentageDifference(float $a, float $b, float $minDiff, float $minPercent): false|float
    {
        if (abs($a - $b) < $minDiff) {
            return false;
        }
        if ($a === 0.0) {
            throw new InvalidArgumentException("El número inicial no puede ser 0.");
        }

        $percent = abs(($b - $a) / $a) * 100;
        if ($percent < $minPercent) {
            return false;
        }
        return $percent;
    }

    public static function formatInstagramNumber(int $number): string|int
    {
        if ($number >= 1000000) {
            return round($number / 1000000, 1) . 'M';
        }

        if ($number >= 10000) {
            return round($number / 1000, 1) . 'K';
        }

        return $number;
    }
}
