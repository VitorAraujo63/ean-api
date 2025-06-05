<?php

namespace App\Helpers;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LoggerHelper
{
    public static function log($action, $description = null)
    {
        // Log em arquivo
        Log::channel('single')->info("[$action] $description");

        // Log em banco
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
        ]);
    }
}
