<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SectionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SectionItemController extends Controller
{
    public function index(string $section)
    {
        return SectionItem::where('section', $section)->orderBy('position')->orderBy('id')->get();
    }

    public function store(Request $request, string $section)
    {
        $data = $request->validate($this->rules($section, true));

        $item = new SectionItem(Arr::only($data, ['title', 'subtitle', 'text']));
        $item->section = $section;
        $item->position = (int) SectionItem::where('section', $section)->max('position') + 1;
        $item->is_active = $request->boolean('is_active', true);

        if ($request->hasFile('image')) {
            $item->image_path = $request->file('image')->store("sections/{$section}", 'public');
        }

        $item->save();

        return response()->json($item, 201);
    }

    /** Envoi d'image : POST avec le champ `_method=PUT` (formulaire multipart). */
    public function update(Request $request, string $section, SectionItem $item)
    {
        $this->ensureBelongs($section, $item);

        $data = $request->validate($this->rules($section, false));

        $item->fill(Arr::only($data, ['title', 'subtitle', 'text']));

        if ($request->has('is_active')) {
            $item->is_active = $request->boolean('is_active');
        }

        if ($request->hasFile('image')) {
            $this->deleteImage($item);
            $item->image_path = $request->file('image')->store("sections/{$section}", 'public');
        } elseif ($request->boolean('remove_image')) {
            $this->deleteImage($item);
            $item->image_path = null;
        }

        $item->save();

        return $item;
    }

    public function destroy(string $section, SectionItem $item)
    {
        $this->ensureBelongs($section, $item);

        $this->deleteImage($item);
        $item->delete();

        return response()->noContent();
    }

    /** Reçoit la liste des identifiants dans le nouvel ordre. */
    public function reorder(Request $request, string $section)
    {
        $ids = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', Rule::exists('section_items', 'id')->where('section', $section)],
        ])['ids'];

        foreach (array_values($ids) as $position => $id) {
            SectionItem::where('section', $section)->whereKey($id)->update(['position' => $position]);
        }

        return $this->index($section);
    }

    private function rules(string $section, bool $creating): array
    {
        return [
            'title' => [$creating ? 'required' : 'sometimes', 'required', 'string', 'max:150'],
            'subtitle' => ['sometimes', 'nullable', 'string', 'max:150'],
            'text' => ['sometimes', 'nullable', 'string', 'max:600'],
            'is_active' => ['sometimes', 'boolean'],
            'remove_image' => ['sometimes', 'boolean'],
            // Seule la section « bureau » accepte une photo.
            'image' => in_array($section, SectionItem::IMAGE_SECTIONS, true)
                ? ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']
                : ['prohibited'],
        ];
    }

    private function ensureBelongs(string $section, SectionItem $item): void
    {
        abort_unless($item->section === $section, 404);
    }

    private function deleteImage(SectionItem $item): void
    {
        if ($item->image_path) {
            Storage::disk('public')->delete($item->image_path);
        }
    }
}