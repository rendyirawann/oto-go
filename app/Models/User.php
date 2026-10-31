<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Cog\Contracts\Ban\Bannable as BannableContract;
use Cog\Laravel\Ban\Traits\Bannable;
use Illuminate\Database\Eloquent\Concerns\HasUuids; // 1. Ini penawar errornya
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class User extends Authenticatable implements BannableContract
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    // 2. Masukkan HasUuids ke dalam use
    use HasFactory, Notifiable, HasRoles, Bannable, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'no_wa',
        'avatar',
        'last_ip',
        'last_login',
        'banned_at',
        'nik',
        'phone',
        'is_active',
        'password',
        'social_id',
        'social_type',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Public URL of the profile picture, or null when there is none.
     *
     * Uploads live on the "public" disk under user/avatar/. Older records may
     * still reference a bundled theme avatar; both are resolved here so views
     * never have to guess, and a missing file yields null so the initials
     * fallback is used instead of a broken image.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        $file = $this->avatar;

        if (! is_string($file) || trim($file) === '') {
            return null;
        }

        if (Str::startsWith($file, ['http://', 'https://'])) {
            return $file;
        }

        if (Storage::disk('public')->exists('user/avatar/' . $file)) {
            return Storage::disk('public')->url('user/avatar/' . $file);
        }

        if (is_file(public_path('assets/media/avatars/' . $file))) {
            return asset('assets/media/avatars/' . $file);
        }

        return null;
    }

    /**
     * Avatar URL that is always renderable.
     *
     * Use this for a plain <img>; the <x-avatar> component prefers
     * avatar_url so it can show initials instead of a placeholder image.
     */
    public function getAvatarDisplayUrlAttribute(): string
    {
        return $this->avatar_url ?? asset('assets/media/avatars/blank.png');
    }

    /** Up to two uppercase initials, used as the avatar fallback. */
    public function getInitialsAttribute(): string
    {
        $words = preg_split('/\s+/', trim((string) $this->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($words === []) {
            return 'U';
        }

        $initials = mb_substr($words[0], 0, 1);

        if (isset($words[1])) {
            $initials .= mb_substr($words[1], 0, 1);
        }

        return Str::upper($initials);
    }

    /** Primary role name for display purposes. */
    public function getRoleLabelAttribute(): string
    {
        return $this->roles->first()->name ?? 'User';
    }
}
