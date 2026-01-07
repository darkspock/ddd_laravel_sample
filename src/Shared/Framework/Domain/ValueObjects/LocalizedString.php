<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Domain\ValueObjects;

use Src\Shared\Framework\Domain\Enums\LanguageEnum;
use Src\Shared\Framework\Helpers\MixedHelper;
use JsonException;

final class LocalizedString implements JsonPersistenceInterface, ValueObjectInterface
{
    /** @var string[] */
    public array $values;

    /**
     * @param  array<string>  $values
     */
    public function __construct(array $values)
    {
        $this->values = $values;
    }

    public static function fromLanguageOrNull(?string $value, LanguageEnum $language): ?self
    {
        if ($value === null) {
            return null;
        }

        return new self(
            [
                $language->value => $value,
            ]
        );
    }

    public static function fromLanguage(string $value, LanguageEnum $language): self
    {
        return new self(
            [
                $language->value => $value,
            ]
        );
    }

    public function get(LanguageEnum $language): ?string
    {
        $value = $this->values[$language->value] ?? null;
        if ($value === 'null') {
            return null;
        }

        return $value;
    }

    public function set(LanguageEnum $language, string $value): void
    {
        $this->values[$language->value] = $value;
    }

    public static function fromJson(?string $json): self
    {
        if (empty($json) || $json === 'null') {
            return new self([]);
        }
        $values = MixedHelper::getStringArray($json);

        return new self($values);
    }

    public function toJson(): string
    {
        try {
            return json_encode(
                [
                    'en' => $this->values['en'] ?? null,
                    'es' => $this->values['es'] ?? null,
                ],
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $e) {
            return '';
        }
    }

    public function toJsonOrNull(): ?string
    {
        if (count(array_filter($this->values)) === 0) {
            return null;
        }

        try {
            return json_encode(
                [
                    'en' => $this->values['en'] ?? null,
                    'es' => $this->values['es'] ?? null,
                ],
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $e) {
            return '';
        }
    }

    /**
     * Check at least spanish is defined
     */
    public function isEmpty(): bool
    {
        if (! isset($this->values['es'])) {
            return true;
        }
        if ($this->values['es'] === 'null') {
            return true;
        }

        return empty($this->values['es']);
    }
}
