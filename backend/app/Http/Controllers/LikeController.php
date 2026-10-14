<?php

namespace App\Http\Controllers;

use App\Models\Publication;
use Illuminate\Http\Request;

/** « J'aime » : un appel bascule entre aimer et ne plus aimer. Réservé aux membres actifs. */
class LikeController extends Controller
{
    public function __invoke(Request $request, Publication $publication)
    {
        abort_unless($publication->published_at && $publication->published_at->isPast(), 404);

        $result = $publication->likedBy()->toggle($request->user()->id);

        return [
            'liked' => count($result['attached']) > 0,
            'likes_count' => $publication->likedBy()->count(),
        ];
    }
}