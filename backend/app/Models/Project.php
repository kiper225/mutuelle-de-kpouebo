<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Project extends Model
{
    public const STATUSES = ['en_cours', 'realise', 'a_venir'];

    protected $fillable = [
        'title', 'summary', 'description', 'status', 'progress', 'location', 'budget', 'starts_on', 'ends_on', 'fundraiser_id',
    ];

    protected $appends = ['image_url'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'progress' => 'integer',
            'budget' => 'integer',
            'position' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    public function fundraiser(): BelongsTo
    {
        return $this->belongsTo(Fundraiser::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }
}