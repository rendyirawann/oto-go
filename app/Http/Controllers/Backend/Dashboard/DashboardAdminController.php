<?php

namespace App\Http\Controllers\Backend\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ActivityDiff;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

class DashboardAdminController extends Controller
{
    /** How many days the activity trend chart covers. */
    private const TREND_DAYS = 14;

    /** Aggregates are cached briefly per user: fresh enough, cheap on reload. */
    private const CACHE_SECONDS = 60;

    public function index(): View
    {
        $user = auth()->user();
        $isSuperadmin = $user->hasRole(['Superadmin', 'superadmin']);

        $aggregates = $this->cached("dashboard.aggregates.{$user->getKey()}", fn () => [
            'stats' => $this->stats($isSuperadmin, $user),
            'trend' => $this->trend($isSuperadmin, $user),
            'breakdown' => $this->breakdown($isSuperadmin, $user),
        ]);

        return view('backend.dashboard.index', [
            'isSuperadmin' => $isSuperadmin,
            'stats' => $aggregates['stats'],
            'trend' => $aggregates['trend'],
            'breakdown' => $aggregates['breakdown'],
            'recent' => $this->recent($isSuperadmin, $user),
            'topUsers' => $isSuperadmin ? $this->topUsers() : collect(),
        ]);
    }

    /**
     * Cache wrapper that degrades to a direct computation when the cache
     * store is unavailable, so the dashboard never fails because of Redis.
     */
    private function cached(string $key, \Closure $compute): array
    {
        try {
            return Cache::remember($key, self::CACHE_SECONDS, $compute);
        } catch (\Throwable) {
            return $compute();
        }
    }

    /** Base activity query, narrowed to the current user unless Superadmin. */
    private function activityQuery(bool $isSuperadmin, User $user)
    {
        $query = Activity::query();

        if (! $isSuperadmin) {
            $query->where('causer_id', $user->getKey());
        }

        return $query;
    }

    /** @return array<string,int|string> */
    private function stats(bool $isSuperadmin, User $user): array
    {
        // Range instead of whereDate() so the created_at index is usable.
        $today = (clone $this->activityQuery($isSuperadmin, $user))
            ->where('created_at', '>=', today())
            ->where('created_at', '<', today()->addDay())
            ->count();

        $week = (clone $this->activityQuery($isSuperadmin, $user))
            ->where('created_at', '>=', now()->subWeek())
            ->count();

        return [
            'users' => $isSuperadmin ? User::count() : null,
            'active_users' => $isSuperadmin ? User::where('is_active', true)->count() : null,
            'roles' => $isSuperadmin ? Role::count() : null,
            'online' => $isSuperadmin ? $this->onlineSessions() : null,
            'activity_today' => $today,
            'activity_week' => $week,
            'last_login' => optional($user->last_login)->diffForHumans() ?? 'Pertama kali',
            'last_ip' => $user->last_ip ?: '-',
        ];
    }

    /**
     * Sessions touched in the last five minutes.
     *
     * Only meaningful with the database session driver; returns null for any
     * other driver so the card can be hidden instead of showing a wrong zero.
     */
    private function onlineSessions(): ?int
    {
        if (config('session.driver') !== 'database' || ! Schema::hasTable('sessions')) {
            return null;
        }

        return DB::table('sessions')
            ->where('last_activity', '>=', now()->subMinutes(5)->getTimestamp())
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');
    }

    /**
     * Daily activity counts for the trend chart.
     *
     * Grouped in SQL: DATE() exists on PostgreSQL, MySQL and SQLite alike.
     *
     * @return array{labels: list<string>, data: list<int>}
     */
    private function trend(bool $isSuperadmin, User $user): array
    {
        $from = today()->subDays(self::TREND_DAYS - 1);

        $counts = (clone $this->activityQuery($isSuperadmin, $user))
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupByRaw('DATE(created_at)')
            ->toBase()
            ->get()
            ->mapWithKeys(fn ($row) => [substr((string) $row->day, 0, 10) => (int) $row->total]);

        $labels = [];
        $data = [];

        foreach (CarbonPeriod::create($from, today()) as $day) {
            $labels[] = $day->translatedFormat('d M');
            $data[] = (int) ($counts[$day->toDateString()] ?? 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * Share of create / update / delete / auth events over the trend window.
     *
     * @return array<string,array{label:string,count:int,color:string,icon:string}>
     */
    private function breakdown(bool $isSuperadmin, User $user): array
    {
        $rows = (clone $this->activityQuery($isSuperadmin, $user))
            ->where('created_at', '>=', today()->subDays(self::TREND_DAYS - 1))
            ->get(['id', 'log_name', 'description', 'properties']);

        $buckets = [
            'create' => ['label' => 'Tambah data', 'count' => 0, 'color' => 'primary', 'icon' => 'ki-plus-square'],
            'update' => ['label' => 'Ubah data', 'count' => 0, 'color' => 'warning', 'icon' => 'ki-pencil'],
            'delete' => ['label' => 'Hapus data', 'count' => 0, 'color' => 'danger', 'icon' => 'ki-trash'],
            'auth' => ['label' => 'Login & logout', 'count' => 0, 'color' => 'success', 'icon' => 'ki-entrance-right'],
        ];

        foreach ($rows as $row) {
            $buckets[$this->bucketFor($row)]['count']++;
        }

        return $buckets;
    }

    /** Map an activity onto one of the breakdown buckets. */
    private function bucketFor(Activity $activity): string
    {
        return match (ActivityDiff::type($activity)) {
            'login', 'logout' => 'auth',
            'delete' => 'delete',
            'update' => 'update',
            default => 'create',
        };
    }

    private function recent(bool $isSuperadmin, User $user)
    {
        return (clone $this->activityQuery($isSuperadmin, $user))
            ->with('causer:id,name,email,avatar')
            ->latest()
            ->limit(8)
            ->get();
    }

    /** Most active users over the trend window. */
    private function topUsers()
    {
        $counts = Activity::query()
            ->where('created_at', '>=', today()->subDays(self::TREND_DAYS - 1))
            ->whereNotNull('causer_id')
            ->select('causer_id', DB::raw('COUNT(*) as total'))
            ->groupBy('causer_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $users = User::whereIn('id', $counts->pluck('causer_id'))
            ->get(['id', 'name', 'email', 'avatar', 'username'])
            ->keyBy('id');

        return $counts
            ->map(fn ($row) => [
                'user' => $users->get($row->causer_id),
                'total' => (int) $row->total,
            ])
            ->filter(fn ($row) => $row['user'] !== null)
            ->values();
    }
}
