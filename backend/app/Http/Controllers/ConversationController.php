<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;

/** Messagerie privée entre membres actifs (conversations à deux). */
class ConversationController extends Controller
{
    /** Annuaire : retrouver un membre par son nom pour lui écrire (nom et photo uniquement). */
    public function directory(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        return User::where('status', User::STATUS_ACTIVE)
            ->where('id', '!=', $request->user()->id)
            ->when($q !== '', fn ($query) => $query->where('name', 'like', '%'.addcslashes($q, '%_\\').'%'))
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'photo_path']);
    }

    /** Liste des conversations : dernier message et nombre de messages non lus. */
    public function index(Request $request)
    {
        $me = $request->user()->id;

        $latestIds = Message::selectRaw('MAX(id) as id')
            ->where(fn ($q) => $q->where('sender_id', $me)->orWhere('recipient_id', $me))
            ->groupByRaw('CASE WHEN sender_id = ? THEN recipient_id ELSE sender_id END', [$me])
            ->pluck('id');

        $unread = Message::where('recipient_id', $me)->whereNull('read_at')
            ->selectRaw('sender_id, COUNT(*) as total')
            ->groupBy('sender_id')
            ->pluck('total', 'sender_id');

        return Message::with(['sender:id,name,photo_path', 'recipient:id,name,photo_path'])
            ->whereIn('id', $latestIds)
            ->orderByDesc('id')
            ->get()
            ->map(function (Message $m) use ($me, $unread) {
                $other = $m->sender_id === $me ? $m->recipient : $m->sender;

                return [
                    'user' => $other->only(['id', 'name', 'photo_url']),
                    'last_message' => ['body' => $m->body, 'created_at' => $m->created_at, 'mine' => $m->sender_id === $me],
                    'unread' => (int) ($unread[$other->id] ?? 0),
                ];
            })
            ->values();
    }

    /** Les 100 derniers messages échangés avec un membre ; marque les messages reçus comme lus. */
    public function show(Request $request, User $user)
    {
        $this->ensurePartner($request, $user);
        $me = $request->user()->id;

        Message::where('sender_id', $user->id)->where('recipient_id', $me)->whereNull('read_at')->update(['read_at' => now()]);

        $messages = Message::where(fn ($q) => $q->where('sender_id', $me)->where('recipient_id', $user->id))
            ->orWhere(fn ($q) => $q->where('sender_id', $user->id)->where('recipient_id', $me))
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->reverse()
            ->values();

        return ['user' => $user->only(['id', 'name', 'photo_url']), 'messages' => $messages];
    }

    public function store(Request $request, User $user)
    {
        $this->ensurePartner($request, $user);

        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        $message = new Message($data);
        $message->sender_id = $request->user()->id;
        $message->recipient_id = $user->id;
        $message->save();

        return response()->json($message, 201);
    }

    public function unreadCount(Request $request)
    {
        return ['count' => Message::where('recipient_id', $request->user()->id)->whereNull('read_at')->count()];
    }

    private function ensurePartner(Request $request, User $user): void
    {
        abort_if($user->id === $request->user()->id, 422, 'Vous ne pouvez pas vous écrire à vous-même.');
        abort_unless($user->isActive(), 404, 'Membre introuvable.');
    }
}