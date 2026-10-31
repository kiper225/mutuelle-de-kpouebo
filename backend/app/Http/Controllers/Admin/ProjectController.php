<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    private const FIELDS = [
        'title', 'summary', 'description', 'status', 'progress', 'location', 'budget', 'starts_on', 'ends_on', 'fundraiser_id',
    ];

    public function index()
    {
        return Project::orderBy('position')->orderByDesc('id')->get();
    }

    public function show(Project $project)
    {
        return $project;
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules($request, true));

        $project = new Project(Arr::only($data, self::FIELDS));
        $project->position = (int) Project::max('position') + 1;
        $project->is_active = $request->boolean('is_active', true);

        if ($request->hasFile('image')) {
            $project->image_path = $request->file('image')->store('projects', 'public');
        }

        $project->save();

        return response()->json($project, 201);
    }

    /** Envoi d'image : POST avec le champ `_method=PUT` (formulaire multipart). */
    public function update(Request $request, Project $project)
    {
        $data = $request->validate($this->rules($request, false));

        $project->fill(Arr::only($data, self::FIELDS));

        if ($request->has('is_active')) {
            $project->is_active = $request->boolean('is_active');
        }

        if ($request->hasFile('image')) {
            $this->deleteImage($project);
            $project->image_path = $request->file('image')->store('projects', 'public');
        } elseif ($request->boolean('remove_image')) {
            $this->deleteImage($project);
            $project->image_path = null;
        }

        $project->save();

        return $project;
    }

    public function destroy(Project $project)
    {
        $this->deleteImage($project);
        $project->delete();

        return response()->noContent();
    }

    /** Reçoit la liste des identifiants dans le nouvel ordre. */
    public function reorder(Request $request)
    {
        $ids = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:projects,id'],
        ])['ids'];

        foreach (array_values($ids) as $position => $id) {
            Project::whereKey($id)->update(['position' => $position]);
        }

        return $this->index();
    }

    private function rules(Request $request, bool $creating): array
    {
        return [
            'title' => [$creating ? 'required' : 'sometimes', 'required', 'string', 'max:150'],
            'summary' => ['sometimes', 'nullable', 'string', 'max:300'],
            'description' => ['sometimes', 'nullable', 'string', 'max:3000'],
            'status' => [$creating ? 'required' : 'sometimes', Rule::in(Project::STATUSES)],
            'progress' => ['sometimes', 'nullable', 'integer', 'between:0,100'],
            'location' => ['sometimes', 'nullable', 'string', 'max:150'],
            'budget' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:2000000000'],
            'starts_on' => ['sometimes', 'nullable', 'date'],
            'ends_on' => ['sometimes', 'nullable', 'date', Rule::when($request->filled('starts_on'), 'after_or_equal:starts_on')],
            'fundraiser_id' => ['sometimes', 'nullable', 'integer', 'exists:fundraisers,id'],
            'is_active' => ['sometimes', 'boolean'],
            'remove_image' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    private function deleteImage(Project $project): void
    {
        if ($project->image_path) {
            Storage::disk('public')->delete($project->image_path);
        }
    }
}