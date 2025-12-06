<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    use HasFactory;

    protected $with = ['product', 'user'];

    protected $fillable = [
        'product_id',
        'user_id',
        'seats_total',
        'seats_used',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'date',
    ];

    public function getSeatsAvailableAttribute(): int
    {
        return max(0, $this->seats_total - $this->seats_used);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
