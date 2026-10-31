<?php

namespace App\Services\Ai\Tools;

use App\Services\Ai\AgentContext;
use App\Support\Brand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Read-only platform numbers for staff. Never offered to customers, and
 * refuses if a non-staff request somehow reaches it.
 */
class PlatformOverviewTool implements AiTool
{
    public function name(): string
    {
        return 'platform_overview';
    }

    public function availableFor(AgentContext $context): bool
    {
        return $context->isStaff;
    }

    public function definition(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->name(),
                'description' => 'Ringkasan statistik platform OtoGo untuk staf: jumlah pengguna per peran, '
                    . 'jumlah artikel blog, dan aktivitas 7 hari terakhir. Hanya-baca.',
                'parameters' => ['type' => 'object', 'properties' => new \stdClass()],
            ],
        ];
    }

    public function run(array $arguments, AgentContext $context): array
    {
        if (! $context->isStaff) {
            return ['content' => ['error' => 'Akses ditolak: data platform hanya untuk staf pengelola.']];
        }

        $data = [
            'aplikasi' => $this->appName(),
            'waktu' => now()->translatedFormat('l, d F Y H:i') . ' WIB',
            'pengguna_total' => DB::table('users')->count(),
            'pengguna_aktif' => Schema::hasColumn('users', 'is_active')
                ? DB::table('users')->where('is_active', true)->count()
                : null,
            'pengguna_per_peran' => DB::table('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->select('roles.name', DB::raw('COUNT(*) as total'))
                ->groupBy('roles.name')
                ->orderByDesc('total')
                ->pluck('total', 'roles.name')
                ->all(),
        ];

        if (Schema::hasTable('posts')) {
            $data['artikel_blog'] = Schema::hasColumn('posts', 'status')
                ? DB::table('posts')->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total', 'status')->all()
                : DB::table('posts')->count();
        }

        if (Schema::hasTable('activity_log')) {
            $since = now()->subDays(7);
            $data['aktivitas_7_hari'] = DB::table('activity_log')->where('created_at', '>=', $since)->count();
            $data['aktivitas_per_jenis_7_hari'] = DB::table('activity_log')
                ->where('created_at', '>=', $since)
                ->select('log_name', DB::raw('COUNT(*) as total'))
                ->groupBy('log_name')
                ->orderByDesc('total')
                ->limit(8)
                ->pluck('total', 'log_name')
                ->all();
        }

        return ['content' => $data];
    }

    private function appName(): string
    {
        try {
            return (string) Brand::get('name', config('app.name'));
        } catch (\Throwable) {
            return (string) config('app.name');
        }
    }
}
