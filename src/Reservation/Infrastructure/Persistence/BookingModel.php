<?php

declare(strict_types=1);

namespace Src\Reservation\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $client_id
 * @property string $restaurant_id
 * @property string $date
 * @property string $time
 * @property int $party_size
 * @property string $status
 * @property string|null $special_requests
 * @property string|null $confirmed_at
 * @property string|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property string $created_at
 * @property string $updated_at
 * @property-read Collection<int, BookingProductModel> $products
 */
final class BookingModel extends Model
{
    protected $table = 'bookings';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'client_id',
        'restaurant_id',
        'date',
        'time',
        'party_size',
        'status',
        'special_requests',
        'confirmed_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'party_size' => 'integer',
    ];

    /**
     * @return HasMany<BookingProductModel, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(BookingProductModel::class, 'booking_id', 'id');
    }
}
