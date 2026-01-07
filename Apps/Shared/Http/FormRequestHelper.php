<?php

declare(strict_types=1);

namespace Apps\Shared\Http;

use Src\Shared\Framework\Helpers\MixedHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

use function is_bool;
use function is_string;

use JsonException;
use RuntimeException;

class FormRequestHelper
{
    private AbstractFormRequest $formRequest;
    private bool $jsonRequest = false;

    public function __construct(AbstractFormRequest $formRequest)
    {
        $this->formRequest = $formRequest;
        if ($this->formRequest->isJson()) {
            $this->jsonRequest = true;
        }
    }

    /**
     * NOTE: Returns false on null
     */
    public function getBooleanOrFalse(string $attribute, ?bool $default = null): bool
    {
        $value = $this->input($attribute, $default);
        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int)$value === 1;
        }
        if (is_string($value)) {
            return $value === '1' || $value === 'true' || $value === 'on' || $value === 'yes';
        }
        if ($value === null) {
            return false;
        }
        throw new RuntimeException('Invalid Value ' . $attribute . ' ' . MixedHelper::getString($value));
    }

    public function getStringOrNull(string $attribute, bool $allowNullText = false): ?string
    {
        $value = $this->input($attribute);
        if ($value === null) {
            return null;
        }

        $string = $this->getString($attribute);
        if ($string === 'null' && !$allowNullText) {
            return null;
        }
        return $string;
    }

    public function getIntOrNull(string $attribute): ?int
    {
        $value = $this->input($attribute);
        if ($value === null) {
            return null;
        }

        return $this->getInt($attribute);
    }

    public function getFloatOrNull(string $attribute): ?float
    {
        $value = $this->input($attribute);
        if ($value === null) {
            return null;
        }

        return $this->getFloat($attribute);
    }

    public function getTimeStamp(string $attribute, ?int $default = null): int
    {
        $value = $this->input($attribute, $default);

        if ($value === '' || $value === null) {
            throw new RuntimeException('Invalid Value ' . $attribute . ' null or empty');
        }

        if (is_string($value)) {
            return (new Carbon($value))->getTimestamp();
        }
        throw new RuntimeException('Invalid Value ' . $attribute . ' ' . MixedHelper::getString($value));
    }

    public function getInt(string $attribute, ?int $default = null): int
    {
        $value = $this->input($attribute, $default);
        if (is_numeric($value)) {
            return (int)$value;
        }
        if (is_string($value)) {
            return (int)$value;
        }
        throw new RuntimeException('Invalid Value ' . $attribute . ' ' . MixedHelper::getString($value));
    }

    public function getFloat(string $attribute, ?int $default = null): float
    {
        $value = $this->input($attribute, $default);
        if (is_numeric($value)) {
            return (float)$value;
        }
        if (is_string($value)) {
            return (float)$value;
        }
        throw new RuntimeException('Invalid Value ' . $attribute . ' ' . MixedHelper::getString($value));
    }

    public function getString(string $attribute, ?string $default = null): string
    {
        $value = $this->input($attribute, $default);
        if (is_string($value)) {
            return $value;
        }
        throw new RuntimeException('Invalid Value ' . $attribute . ' ' . MixedHelper::getString($value));
    }

    public function routeString(string $string): string
    {
        $value = $this->formRequest->route($string);
        if (is_string($value)) {
            return $value;
        }
        throw new RuntimeException('Wrong route value ' . MixedHelper::getString($value));
    }

    public function routeStringOrNull(string $string): ?string
    {
        $value = $this->formRequest->route($string);
        if ($value === null) {
            return null;
        }
        if (is_string($value)) {
            return $value;
        }
        throw new RuntimeException('Wrong route value ' . MixedHelper::getString($value));
    }

    /**
     * @throws JsonException
     * @throws InvalidUUIDException
     */
    public function routeUUID(string $value): string
    {
        $value = $this->routeString($value);
        if (Str::isUuid($value) === false) {
            throw new InvalidUUIDException('Wrong route value ' . MixedHelper::getString($value));
        }

        return trim(str_replace(['/', '.', '\\'], '', $value));
    }

    /**
     * @param array<mixed>|null $default
     *
     * @phpstan-ignore-next-line
     */
    public function getArray(string $attribute, ?array $default = null, bool $isJson = false): array
    {
        $value = $this->input($attribute, $default);

        if ($isJson) {
            $value = json_decode(MixedHelper::getString($value), true);
        }

        if ($value === null) {
            return [];
        }
        if (is_array($value)) {
            return $value;
        }
        throw new RuntimeException('Invalid Value ' . $attribute . ' ' . print_r($value, true));
    }

    /**
     * @param array<mixed>|null $default
     * @return array<array<mixed>>
     */
    public function getArrayOfArrays(string $attribute, ?array $default = null, bool $isJson = false): array
    {
        $value = $this->input($attribute, $default);

        if ($isJson) {
            /** @var array<int|string,mixed>|null $value */
            $value = MixedHelper::safeJsonDecode(MixedHelper::getString($value));
        }

        if ($value === null) {
            return [];
        }
        if (is_array($value)) {
            $keys = array_keys($value);

            return $this->rearrangeArray($value, $keys);
        }
        throw new RuntimeException('Invalid Value ' . $attribute . ' ' . print_r($value, true));
    }

    /**
     * @param array<mixed>|null $default
     *
     * @phpstan-ignore-next-line
     */
    public function getArrayOrNull(string $attribute, ?array $default = null, bool $isJson = false): ?array
    {
        $value = $this->input($attribute, $default);
        if ($value === null || $value === '') {
            return null;
        }

        return $this->getArray($attribute, $default, $isJson);
    }

    public function routeInt(string $string): int
    {
        /** @var string|object|null|int $value */
        $value = $this->formRequest->route($string);
        if (is_string($value)) {
            return (int)$value;
        }
        if (is_numeric($value)) {
            return $value;
        }

        throw new RuntimeException('Wrong route value ' . MixedHelper::getString($value));
    }

    /**
     * @return int[]
     */
    public function getIntArray(string $attribute, bool $isJson = false): array
    {
        $array = $this->getArray($attribute, null, $isJson);
        $array = array_filter($array);

        return array_map(static function ($item) {
            return MixedHelper::getInt($item);
        }, $array);
    }

    /**
     * @param string $attribute
     * @param bool $isJson
     * @return array<float|null>
     */
    public function getFloatOrNullArray(string $attribute, bool $isJson = false, bool $filterArray = true): array
    {
        $array = $this->getArray($attribute, null, $isJson);
        if ($filterArray) {
            $array = array_filter($array);
        }

        return array_map(static function ($item) {
            return MixedHelper::getFloatOrNull($item);
        }, $array);
    }

    /**
     * @return ?int[]
     */
    public function getIntArrayOrNull(string $attribute, bool $isJson = false): ?array
    {
        $array = $this->getArrayOrNull($attribute, null, $isJson);

        if ($array === null) {
            return null;
        }
        $array = array_filter($array);

        return array_map(static function ($item) {
            return MixedHelper::getInt($item);
        }, $array);
    }

    /**
     * @return ?string[]
     */
    public function getStringArrayOrNull(string $attribute, bool $isJson = false): ?array
    {
        $array = $this->getArrayOrNull($attribute, null, $isJson);

        if ($array === null) {
            return null;
        }

        return array_map(static function ($item) {
            return MixedHelper::getString($item);
        }, $array);
    }

    /**
     * @return string[]
     */
    public function getStringArray(string $attribute): array
    {
        $array = $this->getArray($attribute);

        return array_map(static function ($item) {
            return MixedHelper::getString($item);
        }, $array);
    }

    public function getBooleanOrNull(string $attribute): ?bool
    {
        $value = $this->input($attribute);
        if ($value === null) {
            return null;
        }

        return $this->getBooleanOrFalse($attribute);
    }

    public function getBoolean(string $attribute, ?bool $default = null): bool
    {
        $value = $this->input($attribute, $default);
        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int)$value === 1;
        }
        if (is_string($value)) {
            return $value === '1' || $value === 'true' || $value === 'on' || $value === 'yes';
        }
        if ($value === null) {
            return false;
        }
        throw new RuntimeException('Invalid Value ' . $attribute . ' ' . MixedHelper::getString($value));
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        $value = null;
        if ($this->jsonRequest) {
            $value = $this->formRequest->json($key, $default);
        }
        if ($value === null) {
            $value = $this->formRequest->input($key, $default);
        }

        return $value;
    }

    /**
     * @param array<mixed> $input
     * @param array<int|string> $keys
     * @return array<array<mixed>>
     */
    private function rearrangeArray(array $input, array $keys): array
    {
        $result = [];

        // Check if all the keys are set in the input array
        $all_keys_set = true;
        foreach ($keys as $key) {
            if (!isset($input[$key])) {
                $all_keys_set = false;
                break;
            }
        }

        if ($all_keys_set) {
            /** @phpstan-ignore-next-line */
            $count = min(array_map('count', array_intersect_key($input, array_flip($keys))));

            for ($i = 0; $i < $count; $i++) {
                /** @var array<int|string,mixed> $item */
                $item = [];
                foreach ($keys as $key) {
                    /** @phpstan-ignore-next-line */
                    $item[$key] = $input[$key][$i];
                }
                $result[] = $item;
            }
        }

        return $result;
    }
}
