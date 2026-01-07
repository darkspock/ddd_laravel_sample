<?php

declare(strict_types=1);

namespace Src\Reservation\Domain\Enums;

enum ProductType: string
{
    case TABLE_RESERVATION = 'table_reservation';
    case MENU = 'menu';
    case BOTTLE_OF_WINE = 'bottle_of_wine';
    case EVENT = 'event';

    /**
     * Get the price in cents for this product type.
     */
    public function getPriceInCents(): int
    {
        return match ($this) {
            self::TABLE_RESERVATION => 0,      // Free
            self::MENU => 3500,                // 35.00
            self::BOTTLE_OF_WINE => 4500,      // 45.00
            self::EVENT => 7500,               // 75.00
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::TABLE_RESERVATION => 'Table Reservation',
            self::MENU => 'Menu',
            self::BOTTLE_OF_WINE => 'Bottle of Wine',
            self::EVENT => 'Event',
        };
    }
}
