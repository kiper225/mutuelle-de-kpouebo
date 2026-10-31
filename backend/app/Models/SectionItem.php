<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Élément d'une section de l'accueil gérée depuis l'administration.
 * bureau : title = nom, subtitle = fonction, text = citation, image = photo
 * axes / valeurs : title + text, avec une image ou une icône facultatives
 */
class SectionItem extends Model
{
    public const SECTIONS = ['bureau', 'axes', 'valeurs'];
    public const IMAGE_SECTIONS = ['bureau', 'axes', 'valeurs'];
    public const ICON_SECTIONS = ['axes', 'valeurs'];

    /** Doit rester identique à la liste de src/axisIcons.js côté site. */
    public const ICONS = [
        'sante', 'education', 'infrastructures', 'eau', 'agriculture', 'jeunesse', 'entraide', 'culture',
        'sport', 'environnement', 'emploi', 'habitat', 'securite', 'dialogue', 'transparence',
        'responsabilite', 'union',
    ];

    protected $fillable = ['title', 'subtitle', 'text', 'icon'];

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