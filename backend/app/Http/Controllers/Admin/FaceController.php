<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Face;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FaceController extends Controller
{
    public function index()
    {
        return Face::orderBy('position')->orderBy('id')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(true));

        $face = new Face(collect($data)->except(['photo', 'is_active', 'remove_photo'])->all());
        $face->position = (int) Face::max('position') + 1;
        $face->is_active = $request->boolean('is_active', true);

        if ($request->hasFile('photo')) {
            $face->photo_path = $request->file('photo')->store('faces', 'public');
        }

        $face->save();

        return response()->json($face, 201);
    }

    /** Envoi de photo : POST avec le champ `_method=PUT` (formulaire multipart). */
    public function update(Request $request, Face $face)
    {
        $data = $request->validate($this->rules(false));

        $face->fill(collect($data)->except(['photo', 'is_active', 'remove_photo'])->all());

        if ($request->has('is_active')) {
            $face->is_active = $request->boolean('is_active');
        }

        if ($request->hasFile('photo')) {
            $this->deletePhoto($face);
            $face->photo_path = $request->file('photo')->store('faces', 'public');
        } elseif ($request->boolean('remove_photo')) {
            $this->deletePhoto($face);
            $face->photo_path = null;
        }

        $face->save();

        return $face;
    }

    public function destroy(Face $face)
    {
        $this->deletePhoto($face);
        $face->delete();

        return response()->noContent();
    }

    public function reorder(Request $request)
    {
        $ids = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:faces,id'],
        ])['ids'];

        foreach (array_values($ids) as $position => $id) {
            Face::whereKey($id)->update(['position' => $position]);
        }

        return $this->index();
    }

    private function rules(bool $creating): array
    {
        return [
            'name' => [$creating ? 'required' : 'sometimes', 'required', 'string', 'max:150'],
            'role' => ['sometimes', 'nullable', 'string', 'max:150'],
            'text' => ['sometimes', 'nullable', 'string', 'max:600'],
            'is_active' => ['sometimes', 'boolean'],
            'remove_photo' => ['sometimes', 'boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    private function deletePhoto(Face $face): void
    {
        if ($face->photo_path) {
            Storage::disk('public')->delete($face->photo_path);
        }
    }
}