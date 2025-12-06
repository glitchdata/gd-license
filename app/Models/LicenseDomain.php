<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LicenseDomain extends Model
{
    use HasFactory;

    protected $fillable = [
        'license_id',
        'domain',
    ];

    public function license()
    {
        return $this->belongsTo(License::class);
    }
}
