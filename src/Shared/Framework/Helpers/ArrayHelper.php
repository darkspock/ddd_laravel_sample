<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Helpers;

use function array_key_exists;

use BackedEnum;

use function count;

use Src\Shared\Framework\Domain\Entities\BaseEntity;
use Src\Shared\Framework\Domain\ValueObjects\IdentifierInterface;
use Src\Shared\Framework\Domain\ValueObjects\IntegerIdentifier;
use Src\Shared\Framework\Domain\ValueObjects\ValueObject;
use Src\Shared\Framework\Infrastructure\Logging\LoggerHelper;

use function is_array;
use function is_float;
use function is_object;

use stdClass;
use Throwable;

final class ArrayHelper
{
    /**
     * @param  array<mixed>  $array
     * @return array<mixed>
     */
    public static function arrayKeysToLowercase(array $array): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $key = is_string($key) ? strtolower($key) : $key;
            if (is_array($value)) {
                $value = self::arrayKeysToLowercase($value);
            }
            $result[$key] = $value;
        }
        return $result;
    }


    /**
     * @param  IntegerIdentifier[]|null  $valueObjects
     * @return int[]|null
     */
    public static function identityArrayToInt(?array $valueObjects): ?array
    {
        if ($valueObjects === null) {
            return null;
        }

        return array_map(static function (IntegerIdentifier $item) {
            return $item->getValue();
        }, $valueObjects);
    }

    /**
     * Merge first level arrays into a single array
     *
     * @param  array<int|string,array<mixed>>  $items
     * @return array<mixed>
     */
    public static function mergeFirstLevel(array $items, bool $keepKey = false): array
    {
        $result = [];
        foreach ($items as $subArray) {
            foreach ($subArray as $key => $item) {
                if ($keepKey) {
                    $result[$key] = $item;
                } else {
                    $result[] = $item;
                }
            }
        }

        return $result;
    }

    /**
     * @template T
     *
     * @param  int[]  $array
     * @param  class-string<T>  $class
     * @return T[]
     */
    public static function intToIdentityArray(array $array, string $class): array
    {
        return array_map(static function (int $id) use ($class) {
            return new $class($id);
        }, $array);
    }

    /**
     * Changes the class of Identities
     *
     * @template T
     *
     * @param  array<IdentifierInterface>  $array
     * @param  class-string<T>  $class
     * @return T[]
     */
    public static function mutateIdentityArray(array $array, string $class): array
    {
        return array_map(static function (IdentifierInterface $id) use ($class) {
            return new $class($id->getValue());
        }, $array);
    }

    /**
     * @template T of IdentifierInterface
     *
     * @param  string[]  $array
     * @param  class-string<T>  $class
     * @return array<T>
     */
    public static function stringToIdentityArray(array $array, $class): array
    {
        /** @var array<T> $res */
        $res = array_map(static function (string $id) use ($class) {
            return new $class($id);
        }, $array);

        return $res;
    }

    /**
     * @param  IdentifierInterface[]|null  $valueObjects
     * @return string[]|null
     */
    public static function identityArrayToString(?array $valueObjects): ?array
    {
        if ($valueObjects === null) {
            return null;
        }

        return array_map(static function ($item) {
            return (string) $item->getValue();
        }, $valueObjects);
    }


    /**
     * @param  BackedEnum[]|null  $valueObjects
     * @return string[]|null
     */
    public static function backedEnumArrayToString(?array $valueObjects): ?array
    {
        if ($valueObjects === null) {
            return null;
        }

        return array_map(static function (BackedEnum $item) {
            return (string) $item->value;
        }, $valueObjects);
    }

    /**
     * @return null|string|int|array<string,mixed>
     *
     * @phpstan-ignore-next-line
     */
    public static function valueObjectToArray(?ValueObject $valueObject): ?array
    {
        if ($valueObject === null) {
            return null;
        }
        $result = [];
        $attributes = get_object_vars($valueObject);
        foreach ($attributes as $key => $attribute) {
            if ($attribute instanceof IdentifierInterface) {
                $attribute = $attribute->getValue();
            }
            if ($attribute instanceof ValueObject) {
                $attribute = self::valueObjectToArray($attribute);
            }
            $result[$key] = $attribute;
        }
        if (count($result) === 1) {
            /** @phpstan-ignore-next-line */
            return current($result);
        }

        return $result;
    }

    /**
     * @param  array<string,mixed>  $array
     */
    public static function getFloatOrNull(array $array, string $attribute): ?float
    {
        $data = $array[$attribute] ?? null;
        if ($data === null) {
            return null;
        }

        return MixedHelper::getFloat($data);
    }

    /**
     * @param  array<string,mixed>  $array
     */
    public static function getIntOrNull(array $array, string $attribute): ?int
    {
        $data = $array[$attribute] ?? null;
        if ($data === null) {
            return null;
        }

        return MixedHelper::getInt($data);
    }

    /**
     * @param  array<string,mixed>  $array
     */
    public static function getStringOrNull(array $array, string $attribute): ?string
    {
        $data = $array[$attribute] ?? null;
        if ($data === null) {
            return null;
        }

        return MixedHelper::getString($data);
    }

    /**
     * @param  array<string,mixed>  $array
     */
    public static function getBoolOrNull(array $array, string $attribute): ?bool
    {
        $data = $array[$attribute] ?? null;
        if ($data === null) {
            return null;
        }

        return MixedHelper::getBoolean($data);
    }

    /**
     * we may have a problem with identities, value not returned by json_decode
     *
     * @param  array<mixed>  $array1
     * @param  array<mixed>  $array2
     */
    public static function arrayEqual(array $array1, array $array2): bool
    {
        if (count($array1) !== count($array2)) {
            return false;
        }
        foreach ($array1 as $key => $value) {
            if (!array_key_exists($key, $array2)) {
                return false;
            }
            // Si ambos valores son arrays, realiza una comparación recursiva
            if (is_array($value) && is_array($array2[$key])) {
                if (!self::arrayEqual($value, $array2[$key])) {
                    return false;
                }
            } // Si ambos valores son objetos, compáralos como JSON
            elseif (is_object($value) && is_object($array2[$key])) {
                if (MixedHelper::safeJson($value) !== MixedHelper::safeJson($array2[$key])) {
                    return false;
                }
            } // Si ambos valores son floats, compáralos con tolerancia
            elseif (is_float($value) && is_float($array2[$key])) {
                if (abs($value - $array2[$key]) > 0.00001) {
                    return false;
                }
            } // Para todos los demás casos, usa una comparación estricta
            elseif ($value !== $array2[$key]) {
                return false;
            }
        }

        return true;
    }


    /**
     * Converts an array of strings to an array of BackedEnum objects.
     *
     * @template T of BackedEnum
     *
     * @param  string[]  $array  An array of strings to be converted.
     * @param  class-string<T>  $class  The class name of the BackedEnum.
     * @return T[] An array of BackedEnum objects.
     */
    public static function stringToBackedEnum(array $array, string $class): array
    {
        /** @var T[] $res */
        $res = [];
        foreach ($array as $value) {
            try {
                $res[] = $class::from($value);
            } catch (Throwable $e) {
                LoggerHelper::logException($e);

                continue;
            }
        }

        return $res;
    }

    /**
     * @template T
     *
     * @param  string[]  $array
     * @param  class-string<T>  $class
     * @return T[]
     */
    public static function tagToBackedEnum(array $array, string $class): array
    {
        /** @var T[] $res */
        $res = array_map(static function (string $value) use ($class) {
            /** @var BackedEnum $baseEnum */
            /** @phpstan-ignore-next-line */
            $baseEnum = $class;

            return $baseEnum::from($value);
        }, $array);

        return $res;
    }

    /**
     * @template T
     *
     * @param  array<T>  $array
     * @return array<T>
     */
    public static function arrayUnique(array $array, bool $keepKeyAssoc = false): array
    {
        $duplicateKeys = [];
        $tmp = [];

        foreach ($array as $key => $val) {
            if (is_object($val)) {
                $val = (array) $val;
            }

            if (!in_array($val, $tmp)) {
                $tmp[] = $val;
            } else {
                $duplicateKeys[] = $key;
            }
        }

        foreach ($duplicateKeys as $key) {
            unset($array[$key]);
        }

        return $keepKeyAssoc ? $array : array_values($array);
    }

    /**
     * @param  BaseEntity[]  $entities
     * @return IdentifierInterface[]
     */
    public static function extractEntitiesIds(array $entities): array
    {
        /** @var IdentifierInterface[] $identities */
        $identities = collect($entities)->pluck('id')->all();

        return $identities;
    }

    /**
     * @param  array<BaseEntity>  $entities
     * @return int[]
     */
    public static function extractEntitiesIdsAsInt(array $entities): array
    {
        /** @var IdentifierInterface[] $identities */
        $identities = collect($entities)->pluck('id')->all();

        return array_map(static fn ($id) => (int) $id->getValue(), $identities);
    }

    /**
     * @param  array<BaseEntity>  $entities
     * @return string[]
     */
    public static function extractEntitiesIdsAsString(array $entities): array
    {
        /** @var IdentifierInterface[] $identities */
        $identities = collect($entities)->pluck('id')->all();

        return array_map(static fn ($id) => (string) $id->getValue(), $identities);
    }

    /**
     * @param  array<int>  $items
     * @return array<string>
     */
    public static function intToString(array $items): array
    {
        return array_map(static function (int $id) {
            return (string) $id;
        }, $items);
    }

    /**
     * @template T
     * @param  T  $data
     * @return T|stdClass
     */
    public static function safeJson($data): mixed
    {
        if (empty($data)) {
            return new stdClass();
        }

        return $data;
    }
}
