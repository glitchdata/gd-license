<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public const TYPE_LOGIN = 'login';
    public const TYPE_PURCHASE = 'purchase';
    public const TYPE_USER_UPDATE = 'user_update';
    public const TYPE_LICENSE_UPDATE = 'license_update';
    public const TYPE_PRODUCT_UPDATE = 'product_update';
}
