<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Helpers;

use function array_key_exists;
use function count;

use Src\Shared\Framework\Domain\Enums\LanguageEnum;
use Src\Shared\Framework\Infrastructure\Logging\LoggerHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

use function is_array;
use function is_bool;
use function is_numeric;
use function is_string;

use JsonException;
use RuntimeException;

final class MixedHelper
{
    public static function isJson(mixed $value): bool
    {
        if (is_string($value) === false) {

            return false;
        }

        return Str::contains($value, ['{']) || Str::contains($value, ['[']);
    }

    public static function cleanString(?string $string): ?string
    {
        if ($string === null) {
            return null;
        }
        $string = trim($string);
        if ($string === '') {
            return null;
        }

        return $string;
    }

    public static function getLocalizedString(mixed $mixed, LanguageEnum $language, ?string $onError = null): string
    {
        if ($mixed === null) {
            return '';
        }

        if (is_string($mixed) && self::isJson($mixed) === false) {
            return $mixed;
        }

        //Check if mixed is json string
        if (is_string($mixed)) {
            if ($mixed === '') {
                return '';
            }
            $mixed = self::safeJsonDecode($mixed);
        }
        if (is_array($mixed) === false) {
            return self::getString($mixed);
        }
        if ($mixed === []) {
            return '';
        }
        if (isset($mixed[$language->value])) {
            return self::getString($mixed[$language->value]);
        }
        if (array_key_exists($language->value, $mixed)) { //null Value
            return '';
        }

        //fallback to english
        if ($language === LanguageEnum::spanish) {
            if (isset($mixed[LanguageEnum::english->value])) {
                return self::getString($mixed[LanguageEnum::english->value]);
            }
        }

        if ($onError) {
            return $onError;
        }
        throw new RuntimeException('Invalid Localized String Value ' . self::safeJson($mixed));
    }

    public static function getLocalizedStringOrNull(
        mixed        $mixed,
        LanguageEnum $language,
    ): ?string {

        if (is_string($mixed) && self::isJson($mixed) === false) {
            return $mixed;
        }

        if ($mixed === null) {
            return null;
        }

        //Check if mixed is json string
        if (is_string($mixed)) {
            if ($mixed === '') {
                return '';
            }
            $mixed = self::safeJsonDecode($mixed);
        }
        if (is_array($mixed) === false) {
            return self::getString($mixed);
        }
        if ($mixed === []) {
            return '';
        }
        if (isset($mixed[$language->value])) {
            return self::getStringOrNull($mixed[$language->value]);
        }

        return null;
    }

    public static function getString(mixed $mixedString): string
    {
        if (is_string($mixedString)) {
            return $mixedString;
        }
        if (is_numeric($mixedString)) {
            return (string)$mixedString;
        }

        return self::safeJson($mixedString);
    }

    public static function getFloat(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float)$value;
        }
        if (is_string($value)) {
            return (float)$value;
        }
        $errorValue = self::safeJson($value);

        throw new RuntimeException('Invalid Float Value ' . $errorValue);
    }

    public static function parseFloat(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float)$value;
        }
        if (is_string($value)) {
            $value = str_replace(',', '.', $value);
            return (float)$value;
        }
        $errorValue = self::safeJson($value);

        throw new RuntimeException('Invalid Float Value ' . $errorValue);
    }

    /**
     * @return ?float
     */
    public static function getFloatOrNull(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            if ($value === 0) {
                return 0.0;
            }
            return (float)$value;
        }
        if (is_string($value)) {
            if ($value === '0') {
                return 0.0;
            }
            return (float)$value;
        }
        throw new RuntimeException('Invalid Float Value ' . self::safeJson($value));
    }

    /**
     * @return array<string|int,mixed>|array<mixed>
     */
    public static function getArray(mixed $mixed): array
    {

        if (is_array($mixed)) {

            return $mixed;
        }
        if (is_string($mixed)) {
            $array = self::safeJsonDecode($mixed);
            if (is_array($array) === false) {
                return [];
            }

            return $array;
        }
        if ($mixed === null) {
            return [];
        }
        throw new RuntimeException('Invalid value for array ' . self::safeJson($mixed));
    }

    /**
     * @return array<string|int,mixed>|array<mixed>|null
     */
    public static function getArrayOrNull(mixed $mixed): ?array
    {

        if ($mixed === null) {
            return null;
        }
        return self::getArray($mixed);
    }

    public static function extractString(mixed $mixed): string
    {
        if (is_string($mixed) && self::isJson($mixed)) {
            $mixed = self::safeJsonDecode($mixed);
        }
        if (is_string($mixed)) {
            return $mixed;
        }
        if (is_numeric($mixed)) {
            return (string)$mixed;
        }
        if (is_array($mixed) && count($mixed) === 1) {
            return self::extractString(array_values($mixed)[0]);
        }
        throw new RuntimeException('Invalid String Value ' . self::safeJson($mixed));
    }

    public static function extractStringOrNull(mixed $mixed): ?string
    {
        if ($mixed === null) {
            return null;
        }
        if (is_string($mixed) && self::isJson($mixed)) {
            $mixed = self::safeJsonDecode($mixed);
        }
        if (is_string($mixed)) {
            return $mixed;
        }
        if (is_numeric($mixed)) {
            return (string)$mixed;
        }
        if (is_array($mixed) && count($mixed) === 1) {
            return self::extractStringOrNull(array_values($mixed)[0]);
        }
        throw new RuntimeException('Invalid String Value ' . self::safeJson($mixed));
    }

    public static function extractInt(mixed $value): int
    {
        if (is_string($value) && self::isJson($value)) {
            $value = self::safeJsonDecode($value);
        }
        if (is_numeric($value)) {
            return (int)$value;
        }
        if (is_string($value)) {
            return (int)$value;
        }
        if (is_array($value) && count($value) === 1) {
            return self::extractInt(array_values($value)[0]);
        }
        throw new RuntimeException('Invalid Int Value ' . self::safeJson($value));
    }

    public static function getInt(mixed $value): int
    {
        if (is_numeric($value)) {
            return (int)$value;
        }
        if (is_string($value)) {
            return (int)$value;
        }
        if (is_array($value) && count($value) > 0) {
            if (is_numeric($value[0])) {
                return (int)$value[0];
            }

            if (is_string($value[0])) {
                return (int)$value[0];
            }
        }
        LoggerHelper::logException(
            new RuntimeException('Invalid Int Value ' . self::safeJson($value))
        );
        throw new RuntimeException('Invalid Int Value ' . self::safeJson($value));
    }

    public static function getIntOrNull(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return (int)$value;
        }
        if (is_string($value)) {
            if ($value === '') {
                return null;
            }

            return (int)$value;
        }
        LoggerHelper::logException(
            new RuntimeException('Invalid Int Value ' . self::safeJson($value))
        );
        throw new RuntimeException('Invalid Int Value ' . self::safeJson($value));
    }

    public static function getBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (bool)$value;

        }
        if (is_string($value)) {
            return $value === 'true' || $value === 'yes' || $value === '1';
        }
        throw new RuntimeException('Invalid Bool Value ' . self::safeJson($value));
    }

    public static function getBooleanOrNull(mixed $value): ?bool
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (bool)$value;

        }
        if (is_string($value)) {
            return $value === 'true' || $value === 'yes' || $value === '1';
        }
        throw new RuntimeException('Invalid Bool Value ' . self::safeJson($value));
    }

    public static function getStringOrNull(mixed $mixedString): ?string
    {
        if ($mixedString === null || $mixedString === 'null') {
            return null;
        }
        if (is_string($mixedString)) {
            return $mixedString;
        }
        if (is_numeric($mixedString)) {
            return (string)$mixedString;
        }

        return self::safeJson($mixedString);
    }

    /**
     * @return string[]
     */
    public static function getStringArray(mixed $mixed): array
    {
        $array = self::getArray($mixed);

        return array_map(
            static function ($item) {
                return self::getString($item);
            },
            $array
        );
    }

    /**
     * @return array<string>|null
     */
    public static function getStringArrayOrNull(mixed $mixed): ?array
    {
        if ($mixed === null) {
            return null;
        }
        $array = self::getArray($mixed);

        return array_map(
            static function ($item) {
                return self::getString($item);
            },
            $array
        );
    }

    /**
     * @return int[]
     */
    public static function getIntArray(mixed $mixed): array
    {
        $array = self::getArray($mixed);

        return array_map(
            static function ($item) {
                return self::getInt($item);
            },
            $array
        );
    }

    public static function getDaterOrNull(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }
        if (is_numeric($value)) {
            return (int)$value;
        }
        if (is_string($value)) {
            return strtotime($value) ?: null;
        }
        throw new RuntimeException('Invalid Date Value ' . self::safeJson($value));
    }

    public static function getDate(mixed $value): int
    {
        if (is_numeric($value)) {
            return (int)$value;
        }
        if (is_string($value)) {
            $date = strtotime($value);
            if ($date === false) {
                throw new RuntimeException('Invalid Date Value ' . self::safeJson($value));
            }
        }
        if ($value instanceof Carbon) {
            return (int)$value->timestamp;
        }
        throw new RuntimeException('Invalid Date Value ' . self::safeJson($value));
    }

    /**
     * If array is empty or null, return null.
     * Remove null values from the array.
     * @return array<string|int,mixed>|array<mixed>|null
     */
    public static function getNonEmptyArrayOrNull(mixed $value): ?array
    {
        if (is_object($value)) {
            $value = self::safeJsonDecode(self::safeJson($value));
        }
        if ($value === null) {
            return null;
        }
        if ($value === '[]' || $value === '{}') {
            return null;
        }
        //Remove null values from the array
        $array = self::getArray($value);
        $array = array_filter($array, static function ($value) {
            return null !== $value && $value !== '';
        });

        return $array;
    }

    public static function getWeightNormalizedOrNull(?float $weight): ?float
    {
        if ($weight === null) {
            return null;
        }

        if ($weight <= 250) {
            return $weight;
        }

        if ($weight <= 1000) {
            return $weight / 10;
        }

        if ($weight <= 10000) {
            if (str_starts_with(self::getString($weight), '1')) {
                return $weight / 10;
            }

            return $weight / 100;
        }

        if ($weight <= 100000) {
            if (str_starts_with(self::getString($weight), '1')) {
                return $weight / 100;
            }

            return $weight / 1000;
        }

        if ($weight <= 1000000) {
            if (str_starts_with(self::getString($weight), '1')) {
                return $weight / 1000;
            }

            return $weight / 10000;
        }

        if ($weight <= 9999999) {
            if (str_starts_with(self::getString($weight), '1')) {
                return $weight / 10000;
            }

            return $weight / 100000;
        }

        throw new RuntimeException('Invalid Weight Value ' . $weight);
    }

    /**
     * @return array<float>
     */
    public static function getFloatArray(string $mixed): array
    {
        $array = self::getArray($mixed);

        return array_map(
            static function ($item) {
                return self::getFloat($item);
            },
            $array
        );
    }

    public static function safeJson(mixed $item): string
    {
        try {
            return json_encode($item, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            LoggerHelper::logException($e);

            return '';
        }
    }

    public static function safeJsonOrNull(mixed $item): ?string
    {
        if ($item === null) {
            return null;
        }
        try {
            return json_encode($item, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            LoggerHelper::logException($e);

            return '';
        }
    }

    /**
     * Remove empty values from a JSON string or return null if the input is null.
     * @param mixed $item
     * @return string|null
     */
    public static function safeCleanJsonOrNull(mixed $item): ?string
    {
        if ($item === null) {
            return null;
        }
        try {
            /** @var array<mixed> $array */
            $array = json_decode(json_encode($item, JSON_THROW_ON_ERROR), true);
            $cleanedArray = array_filter($array, static function ($value) {
                return $value !== null && $value !== '';
            });
            return json_encode($cleanedArray, JSON_THROW_ON_ERROR);

        } catch (JsonException $e) {
            LoggerHelper::logException($e);

            return '';
        }
    }

    public static function safeJsonDecode(string $item): mixed
    {
        try {
            if ($item === '') {
                return '';
            }
            return json_decode($item, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            LoggerHelper::logException(new RuntimeException('Invalid json ' . $item . ' ' . $e->getMessage()));
            return '';
        }
    }
}
