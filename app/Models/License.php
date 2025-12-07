<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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
        'identifier',
    ];

    protected $casts = [
        'expires_at' => 'date',
    ];

    protected $appends = [
        'inspect_uri',
    ];

    protected static function booted(): void
    {
        static::creating(function (License $license) {
            if (empty($license->identifier)) {
                $license->identifier = self::generateIdentifier();
            }
        });

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

    public function getInspectUriAttribute(): string
    {
        return route('licenses.show', $this);
    }

    private static function generateIdentifier(): string
    {
        do {
            $identifier = strtoupper(Str::random(4).'-'.Str::random(4).'-'.Str::random(4));
        } while (self::where('identifier', $identifier)->exists());

        return $identifier;
    }
}
