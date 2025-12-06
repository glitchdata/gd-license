<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    use HasFactory;

    protected $with = ['product', 'user', 'domains'];

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

    protected static function booted(): void
    {
        static::deleting(function (License $license) {
            $license->domains()->delete();
        });
    }

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

    public function domains()
    {
        return $this->hasMany(LicenseDomain::class);
    }
}
