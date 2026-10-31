<?php

namespace App\Support;

use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

/**
 * Turns an activity log entry's recorded properties into a presentable
 * before/after comparison.
 *
 * Controllers store snapshots under the "old" and "new" keys (Spatie's own
 * attribute recording uses "old"/"attributes"), so both shapes are handled.
 */
class ActivityDiff
{
    /** Never surface these, whatever a controller happened to snapshot. */
    private const REDACTED = [
        'password',
        'password_confirmation',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'api_token',
        'social_token',
        'secret',
    ];

    /** Noise that changes on every write and tells the reader nothing. */
    private const IGNORED = [
        'updated_at',
        'created_at',
    ];

    /** Human labels for the fields we show most often. */
    private const LABELS = [
        'name' => 'Nama',
        'username' => 'Username',
        'email' => 'Email',
        'no_wa' => 'No. WhatsApp',
        'phone' => 'Telepon',
        'nik' => 'NIK',
        'avatar' => 'Foto Profil',
        'is_active' => 'Status Aktif',
        'banned_at' => 'Diblokir Pada',
        'last_ip' => 'IP Terakhir',
        'last_login' => 'Login Terakhir',
        'email_verified_at' => 'Email Terverifikasi',
        'guard_name' => 'Guard',
        'permissions' => 'Hak Akses',
        'roles' => 'Role',
    ];

    /**
     * Build the comparison rows for one activity.
     *
     * @return array{
     *     type: string,
     *     rows: list<array{key:string,label:string,old:?string,new:?string,changed:bool}>,
     *     changed: int,
     *     has_old: bool,
     *     has_new: bool
     * }
     */
    public static function for(Activity $activity): array
    {
        $properties = self::toArray($activity->properties);

        $old = self::clean(self::toArray($properties['old'] ?? []));
        $new = self::clean(self::toArray($properties['new'] ?? $properties['attributes'] ?? []));

        $keys = array_values(array_unique([...array_keys($old), ...array_keys($new)]));
        sort($keys);

        $rows = [];
        $changed = 0;

        foreach ($keys as $key) {
            $before = $old[$key] ?? null;
            $after = $new[$key] ?? null;
            $isChanged = $old !== [] && $new !== [] && $before !== $after;

            if ($isChanged) {
                $changed++;
            }

            $rows[] = [
                'key' => $key,
                'label' => self::LABELS[$key] ?? Str::headline($key),
                'old' => $before,
                'new' => $after,
                'changed' => $isChanged,
            ];
        }

        return [
            'type' => self::type($activity),
            'rows' => $rows,
            'changed' => $changed,
            'has_old' => $old !== [],
            'has_new' => $new !== [],
        ];
    }

    /**
     * Classify the entry so the UI can colour and label it.
     *
     * Entries written through ActivityRecorder carry an explicit "event"
     * property; older entries are classified from their wording, and finally
     * from whether they recorded before/after snapshots.
     */
    public static function type(Activity $activity): string
    {
        $properties = self::toArray($activity->properties);
        $event = $properties['event'] ?? null;

        if (is_string($event) && in_array($event, ['create', 'update', 'delete', 'login', 'logout'], true)) {
            return $event;
        }

        $name = Str::lower((string) $activity->log_name . ' ' . (string) $activity->description);

        return match (true) {
            str_contains($name, 'logout') => 'logout',
            str_contains($name, 'login') => 'login',
            str_contains($name, 'hapus'), str_contains($name, 'delete') => 'delete',
            str_contains($name, 'edit'), str_contains($name, 'ubah'),
            str_contains($name, 'update'), str_contains($name, 'perbarui') => 'update',
            str_contains($name, 'tambah'), str_contains($name, 'buat'), str_contains($name, 'create') => 'create',
            // No wording match: fall back to the snapshot shape.
            isset($properties['old'], $properties['new']) => 'update',
            isset($properties['old']) => 'delete',
            isset($properties['new']), isset($properties['attributes']) => 'create',
            default => 'other',
        };
    }

    /** @return array{0:string,1:string} label + Bootstrap colour for a type */
    public static function badge(string $type): array
    {
        return match ($type) {
            'create' => ['Tambah', 'primary'],
            'update' => ['Ubah', 'warning'],
            'delete' => ['Hapus', 'danger'],
            'login' => ['Login', 'success'],
            'logout' => ['Logout', 'secondary'],
            default => ['Lainnya', 'info'],
        };
    }

    /** Request/agent metadata, presented as flat label => value pairs. */
    public static function context(Activity $activity): array
    {
        $properties = self::toArray($activity->properties);
        $agent = self::toArray($properties['agent'] ?? []);
        $request = self::toArray($properties['request'] ?? []);

        return array_filter([
            'IP Address' => $properties['ip'] ?? null,
            'Browser' => $agent['browser'] ?? null,
            'Sistem Operasi' => $agent['os'] ?? null,
            'Perangkat' => self::device($agent),
            'Metode' => $request['method'] ?? null,
            'URL' => $request['url'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    private static function device(array $agent): ?string
    {
        if (! empty($agent['is_desktop'])) {
            return 'Desktop';
        }

        if (! empty($agent['is_mobile'])) {
            return 'Mobile';
        }

        return $agent['device'] ?? null;
    }

    /** @return array<string,mixed> */
    private static function toArray(mixed $value): array
    {
        if ($value instanceof \Illuminate\Support\Collection) {
            $value = $value->all();
        }

        return is_array($value) ? $value : [];
    }

    /**
     * Drop redacted/noisy keys and flatten every remaining value to a string
     * so the view can print it without further type juggling.
     *
     * @return array<string,string>
     */
    private static function clean(array $data): array
    {
        $out = [];

        foreach ($data as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            if (in_array($key, self::IGNORED, true)) {
                continue;
            }

            if (in_array($key, self::REDACTED, true)) {
                $out[$key] = '••••••••';

                continue;
            }

            $out[$key] = self::stringify($value);
        }

        return $out;
    }

    private static function stringify(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'Ya' : 'Tidak';
        }

        if (is_array($value)) {
            // Lists of scalars read far better as "a, b, c" than as JSON.
            $flat = array_filter($value, 'is_scalar');

            if (count($flat) === count($value)) {
                return $value === [] ? '—' : implode(', ', array_map('strval', $value));
            }

            return (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        }

        if (is_object($value)) {
            return (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $string = (string) $value;

        return $string === '' ? '—' : $string;
    }
}
