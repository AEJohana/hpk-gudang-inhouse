<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Logout;

class LogSuccessfulLogout
{
    /**
     * Handle the event.
     */
    public function handle(Logout $event): void
    {
        if ($event->user instanceof User) {
            $user = $event->user;
            ActivityLogger::auth(
                'logout',
                "Pengguna {$user->name} ({$user->role_badge}) keluar dari sistem.",
                $user,
                [
                    'email' => $user->email,
                    'department' => $user->department,
                ]
            );
        }
    }
}
