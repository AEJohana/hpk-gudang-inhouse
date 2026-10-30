<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class ActivityLogger
{
    /**
     * Log a general system activity.
     */
    public static function log(
        string $logName,
        string $event,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?User $causer = null
    ): void {
        try {
            if (!function_exists('activity')) {
                return;
            }

            $currentCauser = $causer ?? (Auth::check() ? Auth::user() : null);

            $req = request();
            $meta = [];
            if ($req) {
                $meta['ip'] = $req->ip();
                $meta['user_agent'] = substr((string)$req->userAgent(), 0, 255);
            }

            $mergedProperties = array_merge($meta, $properties);

            $activity = activity($logName)
                ->event($event)
                ->withProperties($mergedProperties);

            if ($currentCauser) {
                $activity->causedBy($currentCauser);
            }

            if ($subject) {
                $activity->performedOn($subject);
            }

            $activity->log($description);
        } catch (Throwable $e) {
            // Guardrail: activity logging must never crash primary business transactions
            Log::warning("ActivityLogger error [{$logName}.{$event}]: " . $e->getMessage());
        }
    }

    /**
     * Log authentication events (login, logout, failed login).
     */
    public static function auth(string $event, string $description, ?User $user = null, array $properties = []): void
    {
        self::log('auth', $event, $description, $user, $properties, $user);
    }

    /**
     * Log Work Request fabrication events.
     */
    public static function workRequest(string $event, string $description, ?Model $subject = null, array $properties = [], ?User $causer = null): void
    {
        self::log('work_request', $event, $description, $subject, $properties, $causer);
    }

    /**
     * Log Material Handling courier logistics events.
     */
    public static function materialHandling(string $event, string $description, ?Model $subject = null, array $properties = [], ?User $causer = null): void
    {
        self::log('material_handling', $event, $description, $subject, $properties, $causer);
    }

    /**
     * Log Master Component and CAD drawing changes.
     */
    public static function component(string $event, string $description, ?Model $subject = null, array $properties = [], ?User $causer = null): void
    {
        self::log('component', $event, $description, $subject, $properties, $causer);
    }

    /**
     * Log Warehouse Transactions (inbound, outbound, transfer, return).
     */
    public static function transaction(string $event, string $description, ?Model $subject = null, array $properties = [], ?User $causer = null): void
    {
        self::log('transaction', $event, $description, $subject, $properties, $causer);
    }

    /**
     * Log QR & Barcode generation & printing events.
     */
    public static function qr(string $event, string $description, ?Model $subject = null, array $properties = [], ?User $causer = null): void
    {
        self::log('qr_label', $event, $description, $subject, $properties, $causer);
    }

    /**
     * Log QC and Cycle count events.
     */
    public static function qc(string $event, string $description, ?Model $subject = null, array $properties = [], ?User $causer = null): void
    {
        self::log('qc', $event, $description, $subject, $properties, $causer);
    }

    /**
     * Log Administrative & System settings events.
     */
    public static function system(string $event, string $description, ?Model $subject = null, array $properties = [], ?User $causer = null): void
    {
        self::log('system', $event, $description, $subject, $properties, $causer);
    }
}
