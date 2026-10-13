<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Élément d'une section de l'accueil gérée depuis l'administration.
 * bureau : title = nom, subtitle = fonction, text = citation, image = photo
 * axes / valeurs : title + text
 */
class SectionItem extends Model
{
    public const SECTIONS = ['bureau', 'axes', 'valeurs'];
    public const IMAGE_SECTIONS = ['bureau'];

    protected $fillable = ['title', 'subtitle', 'text'];

    protected $appends = ['image_url'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'position' => 'integer'];
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }
}