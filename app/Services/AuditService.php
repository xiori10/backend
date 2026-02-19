<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Registrar acción en bitácora del sistema.
     */
    public static function log(string $action, ?string $description = null): void
    {
        ActivityLog::create([
            // 'user_id' => Auth::id(),
            'user_id' => Auth::id() ?? null,
            'action' => $action,
            'description' => $description,
            'ip_address' => Request::ip(),
        ]);
    }
}
