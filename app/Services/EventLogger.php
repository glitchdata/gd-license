<?php

namespace App\Services;

use App\Models\EventLog;

class EventLogger
{
    public static function log(string $type, ?int $userId = null, array $context = []): EventLog
    {
        return EventLog::create([
            'user_id' => $userId,
            'type' => $type,
            'context' => $context,
        ]);
    }
}
