<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Helpers;

use function array_key_exists;

use ReflectionClass;
use ReflectionProperty;

final class ObjectHelper
{
    public static function copyPublicProperties(object $source, object $target): void
    {
        $sourceReflection = new ReflectionClass($source);
        $targetReflection = new ReflectionClass($target);

        foreach ($sourceReflection->getProperties(ReflectionProperty::IS_PUBLIC) as $sourceProperty) {
            $propertyName = $sourceProperty->getName();
            if ($targetReflection->hasProperty($propertyName)) {
                $target->{$propertyName} = $source->{$propertyName};
            }
        }
    }

    /**
     * @param  class-string  $className
     */
    public static function implements(object $instance, string $className): bool
    {
        /** @var array<string, class-string>|false $classes */
        $classes = class_implements($instance);
        if ($classes === false) {
            return false;
        }

        return array_key_exists($className, $classes);
    }
}
