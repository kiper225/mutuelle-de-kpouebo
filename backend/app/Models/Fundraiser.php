<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Fundraiser extends Model
{
    protected $fillable = ['title', 'summary', 'description', 'goal_amount', 'suggested_amount', 'starts_on', 'ends_on'];

    protected $appends = ['image_url', 'is_open'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'goal_amount' => 'integer',
            'suggested_amount' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(Contribution::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    /** Ouverte aux contributions : active et dans ses dates. */
    public function getIsOpenAttribute(): bool
    {
        $today = now()->startOfDay();

        return (bool) $this->is_active
            && (! $this->starts_on || $this->starts_on->lte($today))
            && (! $this->ends_on || $this->ends_on->gte($today));
    }
}