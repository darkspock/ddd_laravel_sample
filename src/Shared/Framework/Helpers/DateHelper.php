<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Helpers;

use Src\Shared\Framework\Domain\Enums\LanguageEnum;
use DateTime;

final class DateHelper
{
    public const int DAY = 24 * 60 * 60;

    public const int WEEK = 7 * 24 * 60 * 60;

    public const int HOUR = 60 * 60;

    public const int YEAR = 24 * 60 * 60 * 365;

    public static function getDurationWeeksAndDays(int $seconds): string
    {
        if ($seconds < 0 || $seconds > self::YEAR * 10) {
            return '';
        }
        if ($seconds < self::DAY) {
            return '1D';
        }
        $weeks = $seconds / (self::DAY * 7);
        $rest = $weeks - floor($weeks);
        $days = $rest * 7;

        return (floor($weeks) ? floor($weeks) . 'S' : '') . ' ' . ($days ? (floor($days) . 'D') : '');
    }

    public static function getShortDurationDaysAndHours(int $seconds): string
    {
        if ($seconds < 0 || $seconds > self::YEAR * 10) {
            return '';
        }
        if ($seconds < self::DAY) {
            return round(($seconds / (60 * 60)), 1) . ' h';
        }
        if ($seconds < self::DAY * 4) {
            $days = floor($seconds / self::DAY);
            $hours = round(($seconds - $days * self::DAY) / (60 * 60), 1);

            return $days . ' d ' . $hours . ' h';
        }

        return floor($seconds / self::DAY) . ' d';
    }

    public static function getDurationDaysAndHours(int $seconds, LanguageEnum $lang = LanguageEnum::spanish): string
    {
        if ($seconds < 0 || $seconds > self::YEAR * 10) {
            return '';
        }
        if ($seconds < self::DAY) {
            if ($lang === LanguageEnum::spanish) {
                return round(($seconds / (60 * 60)), 1) . ' horas';
            }
            return round(($seconds / (60 * 60)), 1) . ' hours';
        }
        if ($seconds < self::DAY * 4) {
            $days = floor($seconds / self::DAY);
            $hours = round(($seconds - $days * self::DAY) / (60 * 60), 1);

            if ($lang === LanguageEnum::spanish) {
                return $days . ' días y ' . $hours . ' horas';
            }
            return $days . ' days and ' . $hours . ' hours';
        }

        if ($lang === LanguageEnum::spanish) {
            return floor($seconds / self::DAY) . ' días';
        }
        return floor($seconds / self::DAY) . ' days';
    }

    public static function gePrettyDate(
        int $date,
        bool $addSuffix = false,
        LanguageEnum $lang = LanguageEnum::english
    ): string {
        $seconds = time() - $date;
        if ($seconds === 0) {
            return $lang === LanguageEnum::spanish ? 'Justo ahora' : 'just now';
        }
        if ($seconds < 60) { //dont know, check later why 10 years
            if ($lang === LanguageEnum::spanish) {
                return ($addSuffix ? ' hace ' : '') . $seconds . ' segundos';
            }

            return $seconds . ' seconds' . ($addSuffix ? ' ago' : '');
        }
        if ($seconds < self::HOUR) {
            if ($lang === LanguageEnum::spanish) {
                return ($addSuffix ? ' hace ' : '') . round(($seconds / 60), 0) . ' minutos';
            }

            return round(($seconds / 60), 0) . ' minutes' . ($addSuffix ? ' ago' : '');
        }
        if ($seconds < self::DAY) {
            if ($lang === LanguageEnum::spanish) {
                return ($addSuffix ? ' hace ' : '') . round(($seconds / (60 * 60)), 1) . ' horas';
            }

            return round(($seconds / (60 * 60)), 1) . ' hours' . ($addSuffix ? ' ago' : '');
        }
        if ($seconds < self::DAY * 4) {
            $days = floor($seconds / self::DAY);
            $hours = round(($seconds - $days * self::DAY) / (60 * 60), 1);

            if ($lang === LanguageEnum::spanish) {
                return ($addSuffix ? ' hace ' : '') . $days . ' días ' . $hours . ' horas';
            }
            return $days . ' days ' . $hours . ' hours' . ($addSuffix ? ' ago' : '');
        }
        $days = (floor($seconds / self::DAY));
        if ($days < 5) {
            if ($lang === LanguageEnum::spanish) {
                return ($addSuffix ? ' hace ' : '') . $days . ' días';
            }
            return $days . ' days' . ($addSuffix ? ' ago' : '');
        }

        if ($days < 30) {
            return date('l d M, Y', $date);
        }

        return date('Y-m-d H:i:s', $date);
    }

    public static function convertHourToSeconds(int $hours): int
    {
        return $hours * 3600;
    }

    public static function convertWeeksToSeconds(int $weeks): int
    {
        return $weeks * 604800;
    }

    public static function getAge(int $dob): int
    {
        $birthDate = new DateTime('@' . $dob);
        $now = new DateTime(); // current date

        return $now->diff($birthDate)->y;

    }

    public static function getDayName(int $day): string
    {
        $day = ($day % 7) + 1; // Convert the day number to match PHP's system

        return date('l', (int) mktime(0, 0, 0, 1, $day));
    }

    public static function safeStrToTime(?string $time): int
    {
        if ($time === null) {
            return 0;
        }
        $date = strtotime($time);
        if ($date === false) {
            return 0;
        }
        return $date;
    }
}
