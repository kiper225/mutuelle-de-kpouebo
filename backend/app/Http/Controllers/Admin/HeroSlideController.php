<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HeroSlide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HeroSlideController extends Controller
{
    public function index()
    {
        return HeroSlide::orderBy('position')->orderBy('id')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(true));

        $slide = new HeroSlide(collect($data)->except(['image', 'is_active', 'remove_image'])->all());
        $slide->position = (int) HeroSlide::max('position') + 1;
        $slide->is_active = $request->boolean('is_active', true);

        if ($request->hasFile('image')) {
            $slide->image_path = $request->file('image')->store('slides', 'public');
        }

        $slide->save();

        return response()->json($slide, 201);
    }

    /** Envoi d'image : POST avec le champ `_method=PUT` (formulaire multipart). */
    public function update(Request $request, HeroSlide $heroSlide)
    {
        $data = $request->validate($this->rules(false));

        $heroSlide->fill(collect($data)->except(['image', 'is_active', 'remove_image'])->all());

        if ($request->has('is_active')) {
            $heroSlide->is_active = $request->boolean('is_active');
        }

        if ($request->hasFile('image')) {
            $this->deleteImage($heroSlide);
            $heroSlide->image_path = $request->file('image')->store('slides', 'public');
        } elseif ($request->boolean('remove_image')) {
            $this->deleteImage($heroSlide);
            $heroSlide->image_path = null;
        }

        $heroSlide->save();

        return $heroSlide;
    }

    public function destroy(HeroSlide $heroSlide)
    {
        $this->deleteImage($heroSlide);
        $heroSlide->delete();

        return response()->noContent();
    }

    /** Reçoit la liste des identifiants dans le nouvel ordre. */
    public function reorder(Request $request)
    {
        $ids = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:hero_slides,id'],
        ])['ids'];

        foreach (array_values($ids) as $position => $id) {
            HeroSlide::whereKey($id)->update(['position' => $position]);
        }

        return $this->index();
    }

    private function rules(bool $creating): array
    {
        // Lien interne uniquement : commence par « / » mais pas par « // » (adresse externe).
        $path = ['sometimes', 'nullable', 'string', 'max:200', 'regex:/^\/(?!\/)[\w\-\/?=&#%.]*$/'];

        return [
            'title' => [$creating ? 'required' : 'sometimes', 'required', 'string', 'max:150'],
            'eyebrow' => ['sometimes', 'nullable', 'string', 'max:60'],
            'text' => ['sometimes', 'nullable', 'string', 'max:400'],
            'cta_label' => ['sometimes', 'nullable', 'string', 'max:40'],
            'cta_to' => $path,
            'cta2_label' => ['sometimes', 'nullable', 'string', 'max:40'],
            'cta2_to' => $path,
            'is_active' => ['sometimes', 'boolean'],
            'remove_image' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    private function deleteImage(HeroSlide $slide): void
    {
        if ($slide->image_path) {
            Storage::disk('public')->delete($slide->image_path);
        }
    }
}