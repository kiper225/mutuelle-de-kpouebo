<?php

namespace App\Http\Controllers;

use App\Models\Publication;
use Illuminate\Http\Request;

/** Lecture publique des publications (seul l'admin peut en créer : voir Admin\PublicationController). */
class PublicationController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        return Publication::published()
            ->with('author:id,name')
            ->when($request->query('category'), fn ($query, $c) => $query->where('category', $c))
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.addcslashes($q, '%_\\').'%';
                $query->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('excerpt', 'like', $like));
            })
            ->orderByDesc('published_at')
            ->paginate(9);
    }

    public function show(string $slug)
    {
        return Publication::published()->with('author:id,name')->where('slug', $slug)->firstOrFail();
    }
}
