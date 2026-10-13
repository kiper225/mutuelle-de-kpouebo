<?php

namespace App\Http\Controllers;

use App\Models\Face;
use App\Models\HeroSlide;
use App\Models\SectionItem;

/** Contenu public de l'accueil, dans l'ordre choisi par l'admin. */
class HomeContentController extends Controller
{
    public function __invoke()
    {
        $items = SectionItem::where('is_active', true)->orderBy('position')->orderBy('id')->get()->groupBy('section');

        // « managed » : la section contient au moins un élément (même masqué) ; sinon le site garde les exemples de content.js.
        $managed = SectionItem::query()->distinct()->pluck('section');

        return [
            'slides' => HeroSlide::where('is_active', true)->orderBy('position')->orderBy('id')->get(),
            'faces' => Face::where('is_active', true)->orderBy('position')->orderBy('id')->get(),
            'bureau' => $items->get('bureau', collect())->values(),
            'axes' => $items->get('axes', collect())->values(),
            'valeurs' => $items->get('valeurs', collect())->values(),
            'managed' => collect(SectionItem::SECTIONS)->mapWithKeys(fn (string $s) => [$s => $managed->contains($s)]),
        ];
    }
}