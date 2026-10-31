<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /** Chantiers affichés sur le site, dans l'ordre choisi par l'admin (filtre facultatif : ?status=en_cours). */
    public function index(Request $request)
    {
        return Project::where('is_active', true)
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderBy('position')
            ->orderByDesc('id')
            ->get();
    }

    public function show(int $id)
    {
        $project = Project::where('is_active', true)
            ->with('fundraiser:id,title,image_path,is_active,starts_on,ends_on')
            ->findOrFail($id);

        // Une levée de fonds masquée n'est pas proposée.
        if (! $project->fundraiser?->is_active) {
            $project->setRelation('fundraiser', null);
        }

        return $project;
    }
}