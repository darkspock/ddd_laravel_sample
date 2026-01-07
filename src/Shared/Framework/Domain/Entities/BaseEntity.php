<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Domain\Entities;

use BackedEnum;

use function count;

use Src\Shared\Framework\Domain\Events\DomainEvent;
use Src\Shared\Framework\Domain\ValueObjects\AutoIncrementIdentifier;
use Src\Shared\Framework\Domain\ValueObjects\IdentifierInterface;
use Src\Shared\Framework\Domain\ValueObjects\Timestamp;
use Src\Shared\Framework\Helpers\MixedHelper;
use Src\Shared\Framework\Helpers\ObjectHelper;
use Src\Shared\Framework\Helpers\TraitDynamicProperties;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

use function in_array;
use function is_array;
use function is_object;

use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use RuntimeException;

/**
 * @property IdentifierInterface $id
 */
abstract class BaseEntity
{
    use TraitDynamicProperties;

    /** @var array<string,mixed> */
    private array $checkPoint;

    /**
     * @var DomainEvent[]
     */
    protected array $domainEvents = [];

    protected bool $isNew = true;

    protected ?Timestamp $timestamps = null;

    protected function makeCheckPoint(): void
    {
        $this->checkPoint = $this->serialize();
    }

    public function hasChange(): bool
    {
        return MixedHelper::safeJson($this->checkPoint) !== MixedHelper::safeJson($this->serialize());

    }

    /**
     * @param  array<string,mixed>  $manualMappingArray
     * TODO: nested ValueObjects
     * @return static
     */
    public static function autoHydrate(Model $model, array $manualMappingArray = []): self
    {

        $class = new ReflectionClass(static::class);
        $array = [];
        try {
            $element = $class->newInstanceWithoutConstructor();
            $element->setIsNew(false);
            $array = $model->getAttributes();
            $element->assignTimestamp($array);

            if (count($manualMappingArray)) {
                foreach ($manualMappingArray as $key => $item) {
                    unset($array[$key]);
                }
            }

            $params = BaseEntityHydrater::calculateAttributesFromArray($array, $class);

            foreach ($params as $key => $value) {
                $element->{$key} = $value;
                unset($array[$key]);
            }
            foreach ($manualMappingArray as $key => $item) {
                $element->{$key} = $item;
            }

        } catch (ReflectionException $e) {
            Log::error($e->getMessage());
            Log::error(print_r($array, true));
            echo $e->getMessage();
            throw new RuntimeException($e->getMessage());
        }

        return $element;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function assignTimestamp(array $attributes): void
    {
        if (isset($attributes['created_at']) || isset($attributes['updated_at']) || isset($attributes['createdBy']) || isset($attributes['updatedBy'])) {
            $createdAt = isset($attributes['created_at']) ? strtotime(MixedHelper::getString($attributes['created_at'])) : null;
            $updatedAt = isset($attributes['updated_at']) ? strtotime(MixedHelper::getString($attributes['updated_at'])) : null;
            $createdBy = isset($attributes['createdBy']) ? MixedHelper::getString($attributes['createdBy']) : null;
            $updatedBy = isset($attributes['updatedBy']) ? MixedHelper::getString($attributes['updatedBy']) : null;

            $this->timestamps = new Timestamp(
                createdBy: $createdBy,
                updatedBy: $updatedBy,
                createdAt: $createdAt ?: null,
                updatedAt: $updatedAt ?: null,
            );
        }
    }

    /**
     * Copilot Compatibility
     *
     * @return array<DomainEvent>
     */
    final public function releaseEvents(): array
    {
        return $this->pullDomainEvents();
    }

    /**
     * Records only the last event of the same type
     */
    final protected function recordOnce(DomainEvent $domainEvent): void
    {
        foreach ($this->domainEvents as $key => $event) {
            if ($event->getName() === $domainEvent->getName()) {
                unset($this->domainEvents[$key]);
            }
        }
        $this->domainEvents[] = $domainEvent;
    }

    /**
     * @return array<DomainEvent>
     */
    public function pullDomainEvents(): array
    {
        $domainEvents = $this->domainEvents;
        $this->resetDomainEvents();

        return $domainEvents;
    }

    final public function resetDomainEvents(): void
    {
        $this->domainEvents = [];
    }

    public function isNew(): bool
    {
        return $this->isNew;
    }

    public function setIsNew(bool $isNew = true): void
    {
        $this->isNew = $isNew;
    }

    public function timestamps(): ?Timestamp
    {
        return $this->timestamps;
    }

    final protected function record(DomainEvent $domainEvent): void
    {
        foreach ($this->domainEvents as $event) {
            if ($event->getName() === $domainEvent->getName()) {
                return;
            }
        }
        $this->domainEvents[] = $domainEvent;
    }

    /**
     * Records only the last event of the same type
     */
    final protected function recordLast(DomainEvent $domainEvent): void
    {
        foreach ($this->domainEvents as $key => $event) {
            if ($event->getName() === $domainEvent->getName()) {
                unset($this->domainEvents[$key]);
            }
        }
        $this->domainEvents[] = $domainEvent;
    }

    /**
     * @param  array<string>|null  $fieldList
     * @param  array<string>|null  $fieldIgnored
     * @return array<string,mixed>
     */
    public function serialize(?array $fieldList = null, ?array $fieldIgnored = null): array
    {
        $attributesTransformed = [];
        $attributes = $this->calculateAttributeArray($this->getAttributesWithValues(), $fieldList);

        /**
         * @var string $key
         * @var mixed $value
         */
        foreach ($attributes as $key => $value) {
            $valueTransformed = $value;
            if (is_array($value)) {
                $valueTransformed = MixedHelper::safeJson($value);
                $attributesTransformed[$key] = $valueTransformed;

                continue;
            }
            if ($value instanceof BackedEnum) {
                $valueTransformed = $value->value;
                $attributesTransformed[$key] = $valueTransformed;

                continue;
            }
            if ($value instanceof Carbon) {
                $valueTransformed = $value->timestamp;
                $attributesTransformed[$key] = $valueTransformed;

                continue;
            }
            if (is_object($value) && ObjectHelper::implements($value, IdentifierInterface::class)) {
                /** @phpstan-ignore-next-line */
                $valueTransformed = $value->getValue();
            } elseif (is_object($value)) {
                $valueTransformed = MixedHelper::safeJson($value);
            }
            $attributesTransformed[$key] = $valueTransformed;
        }

        if ($fieldIgnored) {
            foreach ($attributesTransformed as $key => $attribute) {
                if (in_array($key, $fieldIgnored, true)) {
                    unset($attributesTransformed[$key]);
                }
            }
        }

        return $attributesTransformed;
    }

    /**
     * @param  array<string,mixed>  $attributeList
     * @param  array<string>|null  $fieldList
     * @return array<string,mixed>
     */
    private function calculateAttributeArray(array $attributeList, ?array $fieldList): array
    {
        if ($fieldList === null) {
            return $attributeList;
        }
        $resArray = [];
        foreach ($fieldList as $field) {
            if (isset($attributeList[$field])) {
                $resArray[$field] = $attributeList[$field];
            }
        }

        return $resArray;
    }

    /**
     * @param  array<string>|null  $names
     * @param  array<string>  $except
     * @return array<string,mixed>
     */
    public function getAttributesWithValues(?array $names = null, array $except = []): array
    {
        $values = [];
        if ($names === null) {
            $names = $this->getPublicAttributeArray();
        }
        foreach ($names as $name) {
            $values[$name] = $this->{$name};
        }
        foreach ($except as $name) {
            unset($values[$name]);
        }

        return $values;
    }

    /**
     * @return array<string>
     */
    public function getPublicAttributeArray(): array
    {
        $class = new ReflectionClass($this);
        $names = [];
        foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic()) {
                $names[] = $property->getName();
            }
        }

        return $names;
    }

    /**
     * @return static
     */
    public function hydrated(): self
    {
        $this->isNew = false;

        return $this;
    }
    /**
     * @return static
     */
    public function hydrateTimestamps(Model $model): self
    {
        $this->assignTimestamp($model->getAttributes());
        return $this;
    }

}
