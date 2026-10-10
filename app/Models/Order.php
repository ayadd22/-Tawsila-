<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'store_id',
        'driver_id',
        'status',
        'delivery_address',
        'notes',
    ];

  
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
        ];
    }

    
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

   
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            OrderStatus::Pending->value,
            OrderStatus::Accepted->value,
            OrderStatus::PickedUp->value,
        ]);
    }

   
    public function scopeForDriver(Builder $query, int $driverId): void
    {
        $query->where('driver_id', $driverId);
    }

   
    public function scopeUnassignedOlderThanThirtyMinutes(Builder $query): void
    {
        $query->whereNull('driver_id')
              ->where('status', OrderStatus::Pending->value)
              ->where('created_at', '<=', Carbon::now()->subMinutes(30));
    }

  
    public function scopeToday(Builder $query): void
    {
        $query->whereDate('created_at', Carbon::today());
    }
}
