<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Publication;
use Illuminate\Http\Request;

/** Commentaires : réservés aux membres actifs. */
class CommentController extends Controller
{
    public function index(Request $request, Publication $publication)
    {
        $this->ensureVisible($request, $publication);

        return $publication->comments()
            ->with('user:id,name,photo_path')
            ->when(! $request->user()->isAdmin(), fn ($q) => $q->whereNull('hidden_at'))
            ->oldest()
            ->paginate(20);
    }

    public function store(Request $request, Publication $publication)
    {
        $this->ensureVisible($request, $publication);

        $data = $request->validate(['body' => ['required', 'string', 'max:1000']]);

        $comment = new Comment($data);
        $comment->publication_id = $publication->id;
        $comment->user_id = $request->user()->id;
        $comment->save();

        return response()->json($comment->load('user:id,name,photo_path'), 201);
    }

    public function destroy(Request $request, Comment $comment)
    {
        abort_unless(
            $request->user()->isAdmin() || $comment->user_id === $request->user()->id,
            403,
            'Vous ne pouvez supprimer que vos propres commentaires.'
        );

        $comment->delete();

        return response()->noContent();
    }

    private function ensureVisible(Request $request, Publication $publication): void
    {
        $isPublished = $publication->published_at && $publication->published_at->isPast();

        abort_unless($isPublished || $request->user()->isAdmin(), 404);
    }
}
