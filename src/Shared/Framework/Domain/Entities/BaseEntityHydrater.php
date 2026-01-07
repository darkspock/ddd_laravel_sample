<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Domain\Entities;

use function array_key_exists;

use BackedEnum;
use Carbon\Carbon as BaseCarbon;
use Src\Shared\Framework\Domain\ValueObjects\IdentifierInterface;
use Src\Shared\Framework\Domain\ValueObjects\ValueObjectInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

use function is_int;
use function is_string;

use JsonException;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use RuntimeException;

final class BaseEntityHydrater
{
    /** @var array<string,mixed> */
    private static array $reflectionCache = [];

    /**
     * @param  array<string,mixed>  $array
     * @param  ReflectionClass  $class
     * @return array<string,mixed>
     */
    public static function calculateAttributesFromArray(
        array $array,
        ReflectionClass $class
    ): array {

        $params = [];
        foreach ($array as $attribute => $value) {
            $key = 'type_' . $class->getFileName() . $attribute;
            try {

                if (array_key_exists($key, self::$reflectionCache)) {
                    /** @var ReflectionNamedType|null $type */
                    $type = self::$reflectionCache[$key];
                } else {
                    /** @var ReflectionNamedType|null $type */
                    $type = $class->getProperty($attribute)->getType();
                    self::$reflectionCache[$key] = $type;
                }

                if ($type === null) {
                    continue;
                }

                if ($value === null) {
                    $params[$attribute] = null;
                } else {
                    $params[$attribute] = self::calculateReflectionValue($type, $value);
                }
            } catch (ReflectionException) {
                self::$reflectionCache[$key] = null;
            }
        }

        return $params;
    }

    /**
     * @return IdentifierInterface|array|mixed
     *
     * @throws ReflectionException
     */
    private static function calculateReflectionValue(ReflectionNamedType $type, mixed $value): mixed
    {
        $newValue = $value;
        $typeName = $type->getName();
        if (is_string($value) && $typeName === 'array') {

            try {
                $newValue = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new RuntimeException('Invalid JSON in database ' . $value);
            }

        }
        if (str_contains($typeName, '\\')) {
            /** @var class-string $objectClass */
            $objectClass = $typeName;

            $key = 'ReflectionClass' . $objectClass;
            if (isset(self::$reflectionCache[$key])) {
                /** @var ReflectionClass $typeClass */
                $typeClass = self::$reflectionCache[$key];
            } else {
                $typeClass = new ReflectionClass($objectClass);
                self::$reflectionCache[$key] = $typeClass;
            }


            if ((is_string($value) || is_int($value)) && $typeClass->isEnum()) {
                /** @var BackedEnum $typeName */
                /** @phpstan-ignore-next-line */
                $newValue = $typeName::from($value);
            }

            $interfaces = $typeClass->getInterfaces();
            if ((is_string($value) || is_int($value)) && $value !== '' && isset($interfaces[IdentifierInterface::class])) {
                /** @var IdentifierInterface $identity */
                /** @phpstan-ignore-next-line */
                $identity = $typeName;
                $newValue = $identity::create($value);
            }
            if ($typeClass->getName() === BaseCarbon::class) {
                Log::error('Carbon Class used directly');
                throw new RuntimeException('Carbon Class used directly');
            }

            if (is_numeric($value) && $typeClass->getName() === Carbon::class) {

                $newValue = Carbon::createFromTimestamp($value);
            } elseif (is_string($value) && $typeClass->getName() === Carbon::class) {

                $newValue = new Carbon(date($value));
            }
            if ($typeClass->implementsInterface(ValueObjectInterface::class)) {
                if ($value === '' && $typeClass->implementsInterface(IdentifierInterface::class)) {
                    return null;
                }
                /** @var ValueObjectInterface $valueObject */
                $valueObject = $typeClass->newInstanceWithoutConstructor();
                self::updateSingleValueObject($valueObject, $typeClass, $value);
                $newValue = $valueObject;
            }

        }

        return $newValue;
    }

    /**
     * @throws ReflectionException
     */
    public static function updateSingleValueObject(ValueObjectInterface $valueObject, ReflectionClass $voClass, mixed $value): void
    {
        /** @var ReflectionNamedType|null $type */
        $type = $voClass->getProperties()[0]->getType();
        if ($type === null) {
            return;
        }
        $properties = $voClass->getProperties();
        $voProperty = $properties[0];
        $valueObject->{$voProperty->getName()} = self::calculateReflectionValue($type, $value);
    }

    /**
     * @throws ReflectionException
     */
    protected static function updateNormalValueObject(string $voName, string $key, ReflectionClass $voClass, mixed $value, string $dbEngine, ValueObjectInterface $valueObject): void
    {
        $valueProperty = str_replace($voName . '_', '', $key);
        /** @var ReflectionNamedType|null $type */
        $type = $voClass->getProperty($valueProperty)->getType();
        if ($type === null) {
            return;
        }
        $valueObject->{$valueProperty} = self::calculateReflectionValue($type, $value);
    }
}
