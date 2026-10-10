<?php

namespace App\Models;

use App\Models\Scopes\ActiveStoreScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    use HasFactory;

    
    protected static function booted(): void
    {
        static::addGlobalScope(new ActiveStoreScope());
    }

    
    protected $fillable = [
        'owner_id',
        'name',
        'description',
        'address',
        'phone',
        'is_suspended',
    ];

   
    protected function casts(): array
    {
        return [
            'is_suspended' => 'boolean',
        ];
    }

    
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

   
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

   
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
