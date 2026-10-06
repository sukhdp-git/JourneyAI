<?php
declare(strict_types=1);

namespace App\Core;

/** Admin activity log. Never pass passwords or secrets in $details. */
final class Activity
{
    public static function log(string $action, string $module, ?int $recordId = null, string $details = ''): void
    {
        try {
            $req = new Request();
            Database::insert('activity_logs', [
                'admin_id' => Auth::id(),
                'action' => substr($action, 0, 50),
                'module' => substr($module, 0, 50),
                'record_id' => $recordId,
                'details' => mb_substr($details, 0, 500),
                'ip' => $req->ip(),
                'user_agent' => $req->userAgent(),
            ]);
        } catch (\Throwable $e) {
            Logger::error('Activity log failed: ' . $e->getMessage());
        }
    }
}
