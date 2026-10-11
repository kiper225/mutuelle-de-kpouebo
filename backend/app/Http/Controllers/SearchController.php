<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Publication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Recherche globale : activités, publications et, pour un membre connecté, sa propre messagerie. */
class SearchController extends Controller
{
    private const ACTIVITY_CATEGORIES = ['projet_en_cours', 'realisation'];

    public function __invoke(Request $request)
    {
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        abort_if(mb_strlen($q) < 2, 422, 'Saisissez au moins 2 caractères.');

        $limit = min(max((int) $request->query('limit', 5), 1), 20);
        $like = '%'.addcslashes($q, '%_\\').'%';

        $publications = Publication::published()
            ->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('excerpt', 'like', $like)->orWhere('body', 'like', $like))
            ->orderByDesc('published_at');

        $format = fn (Publication $p) => [
            'id' => $p->id,
            'title' => $p->title,
            'slug' => $p->slug,
            'category' => $p->category,
            'excerpt' => $p->excerpt ?: Str::limit(strip_tags($p->body), 140),
            'cover_url' => $p->cover_url,
            'published_at' => $p->published_at,
        ];

        $activities = (clone $publications)->whereIn('category', self::ACTIVITY_CATEGORIES)->limit($limit)->get()->map($format)->values();
        $news = (clone $publications)->whereNotIn('category', self::ACTIVITY_CATEGORIES)->limit($limit)->get()->map($format)->values();

        // Messagerie : uniquement pour un membre actif connecté, et uniquement ses propres conversations.
        $user = $request->user('sanctum');
        $canUseMessaging = $user && $user->isActive();
        $messages = collect();
        $members = collect();

        if ($canUseMessaging) {
            $me = $user->id;

            $messages = Message::with(['sender:id,name,photo_path', 'recipient:id,name,photo_path'])
                ->where(fn ($w) => $w->where('sender_id', $me)->orWhere('recipient_id', $me))
                ->where('body', 'like', $like)
                ->orderByDesc('id')
                ->limit($limit)
                ->get()
                ->map(function (Message $m) use ($me) {
                    $other = $m->sender_id === $me ? $m->recipient : $m->sender;

                    return [
                        'id' => $m->id,
                        'snippet' => Str::limit($m->body, 120),
                        'created_at' => $m->created_at,
                        'mine' => $m->sender_id === $me,
                        'with' => $other->only(['id', 'name', 'photo_url']),
                    ];
                })
                ->values();

            $members = User::where('status', User::STATUS_ACTIVE)
                ->where('id', '!=', $me)
                ->where('name', 'like', $like)
                ->orderBy('name')
                ->limit($limit)
                ->get(['id', 'name', 'photo_path'])
                ->map(fn (User $u) => $u->only(['id', 'name', 'photo_url']))
                ->values();
        }

        return [
            'query' => $q,
            'authenticated' => (bool) $canUseMessaging,
            'activities' => $activities,
            'publications' => $news,
            'messages' => $messages,
            'members' => $members,
        ];
    }
}