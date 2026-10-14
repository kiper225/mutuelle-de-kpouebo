<?php

namespace App\Http\Controllers;

use App\Models\Publication;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/** Lecture publique des publications (seul l'admin peut en créer : voir Admin\PublicationController). */
class PublicationController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        return $this->withSocial(Publication::published(), $request->user('sanctum'))
            ->with('author:id,name')
            ->when($request->query('category'), fn ($query, $c) => $query->where('category', $c))
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.addcslashes($q, '%_\\').'%';
                $query->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('excerpt', 'like', $like));
            })
            ->orderByDesc('published_at')
            ->paginate(9);
    }

    public function show(Request $request, string $slug)
    {
        return $this->withSocial(Publication::published(), $request->user('sanctum'))
            ->with('author:id,name')
            ->where('slug', $slug)
            ->firstOrFail();
    }

    /** Ajoute les compteurs (commentaires visibles, « j'aime ») et, si connecté, « aimé par moi ». */
    private function withSocial(Builder $query, ?User $user): Builder
    {
        $query->withCount([
            'comments as comments_count' => fn ($c) => $c->whereNull('hidden_at'),
            'likedBy as likes_count',
        ]);

        if ($user) {
            $query->withExists(['likedBy as liked_by_me' => fn ($l) => $l->where('users.id', $user->id)]);
        }

        return $query;
    }
}