<?php

namespace App\Http\Controllers\Backend\Help;

use App\Http\Controllers\Controller;
use App\Support\ActivityDiff;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\Facades\DataTables;

class LogActivityController extends Controller
{
    public function index(): View
    {
        return view('backend.help.log_activity.index', [
            'isSuperadmin' => auth()->user()->hasRole(['Superadmin', 'superadmin']),
        ]);
    }

    /**
     * Server-side DataTables feed.
     *
     * Visibility rule: Superadmin sees every user's entries; any other role
     * only ever sees the entries they caused.
     */
    public function getDataLogActivity(Request $request): JsonResponse
    {
        $query = $this->scopedQuery();

        $like = $this->likeOperator();

        if ($type = $request->string('type')->toString()) {
            $keywords = $this->keywordsFor($type);

            if ($keywords !== []) {
                $query->where(function ($q) use ($keywords, $like) {
                    foreach ($keywords as $keyword) {
                        $q->orWhere('log_name', $like, "%{$keyword}%")
                            ->orWhere('description', $like, "%{$keyword}%");
                    }
                });
            }
        }

        if ($from = $request->date('from')) {
            $query->where('created_at', '>=', $from->startOfDay());
        }

        if ($to = $request->date('to')) {
            $query->where('created_at', '<=', $to->endOfDay());
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->filterColumn('causer_id', function ($query, $keyword) use ($like) {
                $query->whereHas('causer', fn ($q) => $q->where('name', $like, "%{$keyword}%"));
            })
            ->editColumn('log_name', function (Activity $activity) {
                [$label, $color] = ActivityDiff::badge(ActivityDiff::type($activity));

                return '<span class="badge badge-light-' . $color . ' fw-semibold">' . e($label) . '</span>'
                    . '<span class="d-block fs-8 text-muted mt-1">' . e(Str::limit((string) $activity->log_name, 26)) . '</span>';
            })
            ->addColumn('causer_id', fn (Activity $a) => e($a->causer->name ?? 'Sistem'))
            ->editColumn('description', fn (Activity $a) => e((string) $a->description))
            ->addColumn('created_at', fn (Activity $a) => Carbon::parse($a->created_at)
                ->timezone(config('app.timezone'))
                ->translatedFormat('d M Y, H:i:s'))
            ->addColumn('ip', fn (Activity $a) => e($a->properties['ip'] ?? '-'))
            ->addColumn('device', function (Activity $activity) {
                $agent = $activity->properties['agent'] ?? [];

                if (! empty($agent['is_desktop'])) {
                    return '<i class="ki-outline ki-screen text-primary me-2"></i>Desktop';
                }

                if (! empty($agent['is_mobile'])) {
                    return '<i class="ki-outline ki-phone text-warning me-2"></i>Mobile';
                }

                return '<i class="ki-outline ki-question-2 text-gray-500 me-2"></i>' . e($agent['device'] ?? 'Unknown');
            })
            ->addColumn('action', fn (Activity $activity) => view('backend.help.log_activity._action', [
                'activity' => $activity,
            ])->render())
            ->rawColumns(['log_name', 'device', 'action'])
            ->make(true);
    }

    /** Full-page detail view. */
    public function show(string $id): View
    {
        $activity = $this->scopedQuery()->with('causer')->findOrFail($id);

        return view('backend.help.log_activity.show', [
            'activity' => $activity,
            'diff' => ActivityDiff::for($activity),
            'context' => ActivityDiff::context($activity),
        ]);
    }

    /** Same detail, rendered as a fragment for the in-table modal. */
    public function detail(string $id): JsonResponse
    {
        $activity = $this->scopedQuery()->with('causer')->findOrFail($id);

        return response()->json([
            'title' => (string) $activity->description,
            'html' => view('backend.help.log_activity._detail', [
                'activity' => $activity,
                'diff' => ActivityDiff::for($activity),
                'context' => ActivityDiff::context($activity),
            ])->render(),
        ]);
    }

    /**
     * The one place the visibility rule lives — both the table and the detail
     * endpoints go through it, so a non-Superadmin cannot read another user's
     * entry by guessing its id.
     */
    private function scopedQuery()
    {
        $user = auth()->user();

        $query = Activity::query()->with('causer')->select('activity_log.*')->latest();

        if (! $user->hasRole(['Superadmin', 'superadmin'])) {
            $query->where('causer_id', $user->getKey());
        }

        return $query;
    }

    /** Case-insensitive LIKE, which PostgreSQL spells differently. */
    private function likeOperator(): string
    {
        return Activity::query()->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }

    /** @return list<string> */
    private function keywordsFor(string $type): array
    {
        return match ($type) {
            'create' => ['tambah', 'buat', 'create'],
            'update' => ['edit', 'ubah', 'update'],
            'delete' => ['hapus', 'delete'],
            'login' => ['login'],
            'logout' => ['logout'],
            default => [],
        };
    }
}
