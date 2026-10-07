<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Publication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** Seul l'administrateur crée, modifie et publie. */
class PublicationController extends Controller
{
    public function index(Request $request)
    {
        return Publication::with('author:id,name')
            ->when($request->query('category'), fn ($q, $c) => $q->where('category', $c))
            ->latest()
            ->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, true);

        $publication = new Publication(collect($data)->only(['title', 'category', 'excerpt', 'body'])->all());
        $publication->slug = Publication::uniqueSlug($data['title']);
        $publication->user_id = $request->user()->id;
        $publication->published_at = $request->boolean('publish', true) ? now() : null;

        if ($request->hasFile('cover')) {
            $publication->cover_path = $request->file('cover')->store('publications', 'public');
        }

        $publication->save();

        return response()->json($publication->load('author:id,name'), 201);
    }

    public function show(Publication $publication)
    {
        return $publication->load('author:id,name');
    }

    /** Pour envoyer une image, utiliser POST avec le champ `_method=PUT` (formulaire multipart). */
    public function update(Request $request, Publication $publication)
    {
        $data = $this->validated($request, false);

        $publication->fill(collect($data)->only(['title', 'category', 'excerpt', 'body'])->all()); // le slug reste stable

        if ($request->has('publish')) {
            $publication->published_at = $request->boolean('publish')
                ? ($publication->published_at ?? now())
                : null;
        }

        if ($request->hasFile('cover')) {
            if ($publication->cover_path) {
                Storage::disk('public')->delete($publication->cover_path);
            }
            $publication->cover_path = $request->file('cover')->store('publications', 'public');
        }

        $publication->save();

        return $publication->load('author:id,name');
    }

    public function destroy(Publication $publication)
    {
        if ($publication->cover_path) {
            Storage::disk('public')->delete($publication->cover_path);
        }

        $publication->delete();

        return response()->noContent();
    }

    private function validated(Request $request, bool $creating): array
    {
        $required = $creating ? 'required' : 'sometimes';

        return $request->validate([
            'title' => [$required, 'string', 'max:200'],
            'category' => [$required, Rule::in(Publication::CATEGORIES)],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => [$required, 'string'],
            'cover' => ['nullable', 'image', 'max:4096'],
            'publish' => ['sometimes', 'boolean'],
        ]);
    }
}
