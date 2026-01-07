<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Helpers;

use function count;
use function is_array;

class AssertHelper
{
    /**
     * @param  array<string>  $errors
     */
    public function __construct(private array $errors = [], private string $name = '', private mixed $value = '')
    {
    }

    public function addError(string $error): self
    {
        $this->errors[] = $error;

        return $this;
    }

    public function variable(string $name, mixed $value): self
    {
        $this->name = $name;
        $this->value = $value;

        return $this;
    }

    public function required(): self
    {
        if ($this->value === '' || $this->value === null || $this->value === 'null') {
            $this->errors[] = $this->name . ' is required';
        }

        if (is_array($this->value) && count($this->value) === 0) {
            $this->errors[] = $this->name . ' is required';
        }

        return $this;
    }

    public function gte(int $count): self
    {
        if (MixedHelper::getInt($this->value) < $count) {
            $this->errors[] = $this->name . ' must be greater than or equal to ' . $count;
        }

        return $this;
    }

    public function between(int $min, int $max): self
    {
        if (MixedHelper::getInt($this->value) < $min || MixedHelper::getInt($this->value) > $max) {
            $this->errors[] = $this->name . ' debe estar entre ' . $min . ' y ' . $max;
        }

        return $this;
    }

    public function lte(int $count): self
    {
        if (MixedHelper::getInt($this->value) > $count) {
            $this->errors[] = $this->name . ' must be lower than or equal to ' . $count;
        }

        return $this;
    }

    public function eq(int $count): self
    {
        if (MixedHelper::getInt($this->value) !== $count) {
            $this->errors[] = $this->name . ' must be equal to ' . $count;
        }

        return $this;
    }

    public function arrayGTECount(int $count): self
    {
        if (is_array($this->value) && count($this->value) < $count) {
            $this->errors[] = $this->name . ' must contain more than ' . $count;
        }

        return $this;
    }

    public function arrayEQCount(int $count): self
    {
        if (is_array($this->value) && count($this->value) !== $count) {
            $this->errors[] = $this->name . ' must be equal to ' . $count;
        }

        return $this;
    }

    /**
     * @return array<string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
