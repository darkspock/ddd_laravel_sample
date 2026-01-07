<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Helpers;

use Illuminate\Support\Facades\App;

/**
 * @template T of object
 */
class Container
{
    /**
     * @param class-string<T> $id
     * @return T
     */
    public static function getObjectInstance(string $id): object
    {
        /** @var T $object */
        $object = App::make($id);
        return $object;
    }

}
