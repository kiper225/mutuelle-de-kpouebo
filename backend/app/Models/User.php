<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\MemberResetPassword;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_MEMBER = 'member';

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';

    public const MARITAL_STATUSES = ['celibataire', 'marie', 'divorce', 'veuf'];
    public const GENDERS = ['M', 'F'];

    // `role`, `status`, `member_number` et `joined_at` ne sont volontairement PAS assignables en masse.
    protected $fillable = [
        'name', 'last_name', 'first_names', 'gender', 'email', 'phone',
        'birth_date', 'birth_place', 'father_name', 'mother_name', 'children_count', 'marital_status',
        'profession', 'residence', 'mutuelle_role', 'photo_path', 'password','show_presence'
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $appends = ['photo_url'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birth_date' => 'date',
            'children_count' => 'integer',
            'joined_at' => 'datetime',
            'password' => 'hashed',
            'last_seen_at' => 'datetime',
            'show_presence' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }

    public function scopeMembers(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_MEMBER);
    }

    public function cotisations(): HasMany
    {
        return $this->hasMany(Cotisation::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new MemberResetPassword($token));
    }

    /** Un membre vu depuis moins de ce nombre de secondes est considéré « en ligne ». */
    public const ONLINE_WINDOW = 90;

    /** « En ligne » vu par $viewer : les deux doivent afficher leur statut (réciprocité, comme Messenger). */
    public function isOnlineFor(self $viewer): bool
    {
        return $viewer->show_presence
            && $this->show_presence
            && $this->last_seen_at !== null
            && $this->last_seen_at->gt(now()->subSeconds(self::ONLINE_WINDOW));
    }
}