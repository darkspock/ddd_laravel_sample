<?php

declare(strict_types=1);

namespace Src\Reservation\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $booking_id
 * @property string $product_type
 * @property int $quantity
 * @property int $unit_price
 * @property int $total_price
 * @property string $created_at
 * @property string $updated_at
 */
final class BookingProductModel extends Model
{
    protected $table = 'booking_products';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'booking_id',
        'product_type',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'integer',
        'total_price' => 'integer',
    ];

    /**
     * @return BelongsTo<BookingModel, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(BookingModel::class, 'booking_id', 'id');
    }
}
