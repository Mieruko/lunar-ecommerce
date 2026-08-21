<?php

namespace App\Services;

use App\Models\AdminActivityLog;
use Illuminate\Database\Eloquent\Model;

class AdminActivityLogger
{
    public function log(string $action, ?Model $subject = null, ?array $before = null, ?array $after = null): void
    {
        $authenticatedUser = auth()->user();

        AdminActivityLog::create([
            // Storefront buyers can trigger payment callbacks while authenticated,
            // but they must not appear as admin actors in the audit trail.
            'actor_id' => $authenticatedUser?->isStaff() ? $authenticatedUser->id : null,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'before' => $before,
            'after' => $after,
            'request_id' => request()?->header('X-Request-ID'),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
