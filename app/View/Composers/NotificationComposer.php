<?php

namespace App\View\Composers;

use App\Support\ActivityDiff;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

/**
 * Feeds the topbar notification menu from the activity log.
 *
 * Superadmins see activity from every user; everyone else only sees their
 * own — the same visibility rule the Activity Log page uses.
 */
class NotificationComposer
{
    private const LIMIT = 6;

    public function compose(View $view): void
    {
        $user = auth()->user();

        if ($user === null) {
            $view->with(['notifications' => [], 'notificationCount' => 0]);

            return;
        }

        $query = Activity::query()->with('causer')->latest();

        if (! $user->hasRole(['Superadmin', 'superadmin'])) {
            $query->where('causer_id', $user->getKey());
        }

        $recent = (clone $query)->limit(self::LIMIT)->get();

        $view->with([
            'notifications' => $recent->map(fn (Activity $activity) => $this->present($activity))->all(),
            'notificationCount' => (clone $query)->where('created_at', '>=', now()->subDay())->count(),
        ]);
    }

    /** @return array<string,string> */
    private function present(Activity $activity): array
    {
        [$icon, $color] = $this->badge(ActivityDiff::type($activity));

        return [
            'title' => Str::limit((string) $activity->description, 60),
            'actor' => $activity->causer->name ?? 'Sistem',
            'time' => optional($activity->created_at)->diffForHumans() ?? '-',
            'icon' => $icon,
            'color' => $color,
            // Every role may open the log page; the controller scopes it.
            'url' => route('log-activity.show', $activity->getKey()),
        ];
    }

    /**
     * Icon + colour for an event kind.
     *
     * @return array{0:string,1:string}
     */
    private function badge(string $type): array
    {
        return match ($type) {
            'login' => ['ki-entrance-right', 'success'],
            'logout' => ['ki-exit-right', 'secondary'],
            'delete' => ['ki-trash', 'danger'],
            'update' => ['ki-pencil', 'warning'],
            'create' => ['ki-plus-square', 'primary'],
            default => ['ki-notepad', 'info'],
        };
    }
}
