<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Helpers;

use RuntimeException;

trait TraitDynamicProperties
{
    public function __get(string $name): mixed
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        throw new RuntimeException("Property {$name} does not exist");
    }

    public function __isset(string $name): bool
    {
        return property_exists($this, $name);
    }

    public function __set(string $name, mixed $value): void
    {
        if (property_exists($this, $name)) {
            $this->$name = $value;
        }
    }
}
