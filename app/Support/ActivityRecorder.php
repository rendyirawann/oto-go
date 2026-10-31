<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Jenssegers\Agent\Agent;

/**
 * One entry point for writing audit entries.
 *
 * Only state-changing events are recorded — create, update, delete plus
 * login/logout. Page views are deliberately *not* logged: they would bury the
 * entries that matter without adding anything an access log does not already
 * have.
 *
 * Every entry carries the same property shape so the log table and the
 * before/after diff view can rely on it:
 *
 *   ip      string
 *   agent   { browser, os, device, is_mobile, is_desktop, raw }
 *   request { method, url }
 *   old     array|null   snapshot before the change (update/delete)
 *   new     array|null   snapshot after the change  (create/update)
 */
class ActivityRecorder
{
    /**
     * Event kinds stored under the "event" property.
     *
     * Recording the kind explicitly means the log table, the dashboard
     * breakdown and the diff view never have to guess it from wording.
     */
    public const CREATE = 'create';
    public const UPDATE = 'update';
    public const DELETE = 'delete';
    public const LOGIN = 'login';
    public const LOGOUT = 'logout';

    /** Keys that must never reach the activity log. */
    private const REDACTED = [
        'password',
        'password_confirmation',
        'current_password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'api_token',
    ];

    public static function created(string $logName, string $description, array $new, ?Model $subject = null): void
    {
        self::log($logName, $description, [
            'event' => self::CREATE,
            'new' => self::sanitize($new),
        ], $subject);
    }

    public static function updated(string $logName, string $description, array $old, array $new, ?Model $subject = null): void
    {
        self::log($logName, $description, [
            'event' => self::UPDATE,
            'old' => self::sanitize($old),
            'new' => self::sanitize($new),
        ], $subject);
    }

    public static function deleted(string $logName, string $description, array $old, ?Model $subject = null): void
    {
        self::log($logName, $description, [
            'event' => self::DELETE,
            'old' => self::sanitize($old),
        ], $subject);
    }

    public static function loggedIn(string $description = 'Login berhasil'): void
    {
        self::log('login', $description, ['event' => self::LOGIN]);
    }

    public static function loggedOut(string $description = 'Logout berhasil'): void
    {
        self::log('logout', $description, ['event' => self::LOGOUT]);
    }

    /**
     * Write an entry. Extra properties are merged on top of the standard
     * request/agent context.
     *
     * Failures are swallowed: an audit write must never take down the action
     * it was recording.
     */
    public static function log(string $logName, string $description, array $properties = [], ?Model $subject = null): void
    {
        try {
            if (! function_exists('activity')) {
                return;
            }

            $entry = activity()
                ->useLog($logName)
                ->causedBy(auth()->user())
                ->withProperties(array_merge(self::context(), $properties));

            if ($subject !== null) {
                $entry->performedOn($subject);
            }

            $entry->log($description);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Request, browser and device context for the current request.
     *
     * @return array<string,mixed>
     */
    public static function context(): array
    {
        $request = request();
        $agent = new Agent;
        $agent->setUserAgent((string) $request->userAgent());

        return [
            'ip' => $request->ip(),
            'agent' => [
                'browser' => trim($agent->browser() . ' ' . $agent->version($agent->browser())),
                'os' => trim($agent->platform() . ' ' . $agent->version($agent->platform())),
                'device' => $agent->device(),
                'is_mobile' => $agent->isMobile(),
                'is_desktop' => $agent->isDesktop(),
                'raw' => substr((string) $request->userAgent(), 0, 512),
            ],
            'request' => [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
            ],
        ];
    }

    /**
     * Replace secrets with a mask, keeping the key so the diff still shows
     * that the field was touched.
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    public static function sanitize(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && in_array($key, self::REDACTED, true)) {
                $data[$key] = '••••••••';

                continue;
            }

            if (is_array($value)) {
                $data[$key] = self::sanitize($value);
            }
        }

        return $data;
    }
}
