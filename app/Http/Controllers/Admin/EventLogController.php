<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventLog;
use Illuminate\View\View;

class EventLogController extends Controller
{
    public function index(): View
    {
        $logs = EventLog::latest()->paginate(50);

        return view('admin.logs.events', [
            'logs' => $logs,
        ]);
    }
}
