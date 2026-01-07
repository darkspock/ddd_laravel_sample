<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Infrastructure\Persistence;

use Src\Shared\Framework\Domain\Entities\BaseEntity;
use Src\Shared\Framework\Domain\ValueObjects\AutoIncrementIdentifier;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

abstract class EloquentRepository
{
    /**
     * @param  class-string<Model>  $ARClassName
     * @param  array<array<string,mixed>>  $data
     */
    public function insertBulk(string $ARClassName, array $data): void
    {
        /** @var Model $ARClass */
        /** @phpstan-ignore-next-line */
        $ARClass = $ARClassName;
        $ARClass::query()->insert($data);
    }

    /**
     * @param  string  $ARClassName
     * @param  array<mixed>  $data
     * @param  array<mixed>  $keys
     * @param  array<string>|null  $columns
     * @return void
     *
     */
    public function upsertBulk(string $ARClassName, array $data, array $keys, ?array $columns = null): void
    {
        /** @var Model $ARClass */
        /** @phpstan-ignore-next-line */
        $ARClass = $ARClassName;
        $ARClass::query()->upsert(values: $data, uniqueBy: $keys, update: $columns);
    }


    /**
     * @param  class-string<Model>  $ARClassName
     * @param  array<string,mixed>  $data
     */
    public function upsert(BaseEntity $baseEntity, string $ARClassName, array $data): void
    {
        /** @var Model $ARClass */
        /** @phpstan-ignore-next-line */
        $ARClass = $ARClassName;
        if ($baseEntity->isNew()) {
            /** @var Model $newValue */
            $newValue = $ARClass::query()->create($data);
            if (isset($baseEntity->id) && $baseEntity->id instanceof AutoIncrementIdentifier) {

                /** @var int $id */
                $id = $newValue->getKey();
                if ($id === -1) {

                    throw new RuntimeException('Error getting autoincrement id');
                }
                $baseEntity->id->setAutoIncrement($id);
            }
        } else {
            /** @var Model|null $ar */
            $ar = $ARClass::query()->find($baseEntity->id->getValue());
            if ($ar === null) {
                $ARClass::query()->create($data);
            } else {
                $ar->fill($data);
                $ar->save();
            }
        }
    }
}
