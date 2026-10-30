<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        if ($event->user instanceof User) {
            $user = $event->user;
            ActivityLogger::auth(
                'login',
                "Pengguna {$user->name} ({$user->role_badge}) berhasil masuk ke sistem.",
                $user,
                [
                    'email' => $user->email,
                    'department' => $user->department,
                    'work_area' => $user->work_area,
                ]
            );
        }
    }
}
