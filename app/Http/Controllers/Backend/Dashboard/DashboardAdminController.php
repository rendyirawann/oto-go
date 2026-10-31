<?php

namespace App\Http\Controllers\Backend\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ActivityDiff;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

class DashboardAdminController extends Controller
{
    /** How many days the activity trend chart covers. */
    private const TREND_DAYS = 14;

    public function index(): View
    {
        $user = auth()->user();
        $isSuperadmin = $user->hasRole(['Superadmin', 'superadmin']);

        return view('backend.dashboard.index', [
            'isSuperadmin' => $isSuperadmin,
            'stats' => $this->stats($isSuperadmin, $user),
            'trend' => $this->trend($isSuperadmin, $user),
            'breakdown' => $this->breakdown($isSuperadmin, $user),
            'recent' => $this->recent($isSuperadmin, $user),
            'topUsers' => $isSuperadmin ? $this->topUsers() : collect(),
        ]);
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
        $today = (clone $this->activityQuery($isSuperadmin, $user))
            ->whereDate('created_at', today())
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
     * Rows are grouped in PHP rather than SQL so the query stays portable
     * across MySQL/PostgreSQL date functions.
     *
     * @return array{labels: list<string>, data: list<int>}
     */
    private function trend(bool $isSuperadmin, User $user): array
    {
        $from = today()->subDays(self::TREND_DAYS - 1);

        $counts = (clone $this->activityQuery($isSuperadmin, $user))
            ->where('created_at', '>=', $from)
            ->get(['created_at'])
            ->groupBy(fn ($row) => $row->created_at->toDateString())
            ->map->count();

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
            ->with('causer')
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

        $users = User::whereIn('id', $counts->pluck('causer_id'))->get()->keyBy('id');

        return $counts
            ->map(fn ($row) => [
                'user' => $users->get($row->causer_id),
                'total' => (int) $row->total,
            ])
            ->filter(fn ($row) => $row['user'] !== null)
            ->values();
    }
}
