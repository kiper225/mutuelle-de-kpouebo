<?php

namespace App\Http\Controllers;

use App\Models\ChatState;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/** Messagerie privée entre membres actifs (conversations à deux). */
class ConversationController extends Controller
{
    /** Annuaire : retrouver un membre par son nom pour lui écrire (nom, photo et statut uniquement). */
    public function directory(Request $request)
    {
        $viewer = $request->user();
        $blocked = $this->blockedWith($viewer);
        $q = trim((string) $request->query('q', ''));

        return User::where('status', User::STATUS_ACTIVE)
            ->where('id', '!=', $viewer->id)
            ->when($q !== '', fn ($query) => $query->where('name', 'like', '%'.addcslashes($q, '%_\\').'%'))
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'photo_path', 'last_seen_at', 'show_presence'])
            ->map(fn (User $u) => $u->only(['id', 'name', 'photo_url']) + $this->presence($u, $viewer, $blocked))
            ->values();
    }

    /** Liste des conversations : dernier message, messages non lus, statut. Les discussions supprimées par le membre sont masquées. */
    public function index(Request $request)
    {
        $viewer = $request->user();
        $me = $viewer->id;
        $blocked = $this->blockedWith($viewer);

        $cleared = ChatState::where('user_id', $me)->whereNotNull('cleared_at')->pluck('cleared_at', 'partner_id');

        $latestIds = Message::selectRaw('MAX(id) as id')
            ->where(fn ($q) => $q->where('sender_id', $me)->orWhere('recipient_id', $me))
            ->groupByRaw('CASE WHEN sender_id = ? THEN recipient_id ELSE sender_id END', [$me])
            ->pluck('id');

        $unread = Message::where('recipient_id', $me)->whereNull('read_at')
            ->selectRaw('sender_id, COUNT(*) as total')
            ->groupBy('sender_id')
            ->pluck('total', 'sender_id');

        return Message::with([
                'sender:id,name,photo_path,last_seen_at,show_presence',
                'recipient:id,name,photo_path,last_seen_at,show_presence',
            ])
            ->whereIn('id', $latestIds)
            ->orderByDesc('id')
            ->get()
            ->filter(function (Message $m) use ($me, $cleared) {
                $otherId = $m->sender_id === $me ? $m->recipient_id : $m->sender_id;
                $at = $cleared[$otherId] ?? null;

                // Le dernier message est plus ancien que la suppression : toute la discussion est masquée.
                return ! $at || $m->created_at->gt(Carbon::parse($at));
            })
            ->map(function (Message $m) use ($me, $viewer, $blocked, $unread) {
                $other = $m->sender_id === $me ? $m->recipient : $m->sender;

                return [
                    'user' => $other->only(['id', 'name', 'photo_url']) + $this->presence($other, $viewer, $blocked),
                    'last_message' => [
                        'body' => $m->body,
                        'kind' => $m->kind,
                        'created_at' => $m->created_at,
                        'mine' => $m->sender_id === $me,
                    ],
                    'unread' => (int) ($unread[$other->id] ?? 0),
                ];
            })
            ->values();
    }

    /** Les 100 derniers messages échangés avec un membre (depuis la dernière suppression) ; marque les messages reçus comme lus. */
    public function show(Request $request, User $user)
    {
        $this->ensurePartner($request, $user);
        $viewer = $request->user();
        $me = $viewer->id;

        Message::where('sender_id', $user->id)->where('recipient_id', $me)->whereNull('read_at')->update(['read_at' => now()]);

        $clearedAt = ChatState::where('user_id', $me)->where('partner_id', $user->id)->value('cleared_at');

        $messages = Message::where(function ($q) use ($me, $user) {
                $q->where(fn ($m) => $m->where('sender_id', $me)->where('recipient_id', $user->id))
                    ->orWhere(fn ($m) => $m->where('sender_id', $user->id)->where('recipient_id', $me));
            })
            ->when($clearedAt, fn ($q) => $q->where('created_at', '>', $clearedAt))
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->reverse()
            ->values();

        return [
            'user' => $user->only(['id', 'name', 'photo_url']) + $this->presence($user, $viewer, $this->blockedWith($viewer)),
            'blocked_by_me' => $this->isBlocked($me, $user->id),
            'messages' => $messages,
        ];
    }

    /** Profil d'un membre vu depuis la messagerie : informations publiques uniquement (jamais téléphone ni date de naissance). */
    public function profile(Request $request, User $user)
    {
        $this->ensurePartner($request, $user);
        $viewer = $request->user();
        $isAdmin = $user->role === 'admin';

        return $user->only(['id', 'name', 'photo_url', 'mutuelle_role']) + [
            'member_since' => $user->joined_at?->format('Y'),
            'is_admin' => $isAdmin,
            'can_block' => ! $isAdmin || $viewer->role === 'admin',
            'blocked_by_me' => $this->isBlocked($viewer->id, $user->id),
        ] + $this->presence($user, $viewer, $this->blockedWith($viewer));
    }

    public function store(Request $request, User $user)
    {
        $this->ensurePartner($request, $user);
        $me = $request->user();

        // Blocage : dans les deux sens, aucun message ne passe.
        abort_if($this->isBlocked($me->id, $user->id), 403, 'Vous avez bloqué ce membre. Débloquez-le pour lui écrire.');
        abort_if($this->isBlocked($user->id, $me->id), 403, "Votre message n'a pas pu être envoyé à ce membre.");

        $data = $request->validate([
            'kind' => ['sometimes', Rule::in(['text', 'sticker'])],
            'body' => ['required', 'string', 'max:2000'],
        ]);
        $kind = $data['kind'] ?? 'text';

        abort_if($kind === 'sticker' && ! in_array($data['body'], Message::STICKERS, true), 422, 'Autocollant inconnu.');

        $message = new Message(['body' => $data['body']]);
        $message->kind = $kind;
        $message->sender_id = $me->id;
        $message->recipient_id = $user->id;
        $message->save();

        return response()->json($message, 201);
    }

    /** Supprime la discussion pour le membre seulement : l'autre membre garde la sienne. */
    public function destroy(Request $request, User $user)
    {
        $this->ensurePartner($request, $user, false);
        $me = $request->user();

        // Ce qui n'avait pas été lu ne doit pas rester dans la pastille « non lu ».
        Message::where('sender_id', $user->id)->where('recipient_id', $me->id)->whereNull('read_at')->update(['read_at' => now()]);

        $this->state($me, $user)->forceFill(['cleared_at' => now()])->save();

        return response()->noContent();
    }

    public function block(Request $request, User $user)
    {
        $this->ensurePartner($request, $user, false);
        $me = $request->user();

        abort_if($user->role === 'admin' && $me->role !== 'admin', 422, "L'administration ne peut pas être bloquée.");

        $this->state($me, $user)->forceFill(['blocked_at' => now()])->save();

        return ['blocked_by_me' => true];
    }

    public function unblock(Request $request, User $user)
    {
        $this->ensurePartner($request, $user, false);

        $this->state($request->user(), $user)->forceFill(['blocked_at' => null])->save();

        return ['blocked_by_me' => false];
    }

    public function unreadCount(Request $request)
    {
        return ['count' => Message::where('recipient_id', $request->user()->id)->whereNull('read_at')->count()];
    }

    private function ensurePartner(Request $request, User $user, bool $requireActive = true): void
    {
        abort_if($user->id === $request->user()->id, 422, 'Vous ne pouvez pas vous écrire à vous-même.');
        abort_if($requireActive && ! $user->isActive(), 404, 'Membre introuvable.');
    }

    private function state(User $owner, User $partner): ChatState
    {
        return ChatState::firstOrCreate(['user_id' => $owner->id, 'partner_id' => $partner->id]);
    }

    private function isBlocked(int $blocker, int $blocked): bool
    {
        return ChatState::where('user_id', $blocker)->where('partner_id', $blocked)->whereNotNull('blocked_at')->exists();
    }

    /** Identifiants des membres bloqués par $viewer ou qui l'ont bloqué : on ne voit pas leur présence. */
    private function blockedWith(User $viewer): Collection
    {
        return ChatState::whereNotNull('blocked_at')
            ->where(fn ($q) => $q->where('user_id', $viewer->id)->orWhere('partner_id', $viewer->id))
            ->get(['user_id', 'partner_id'])
            ->map(fn (ChatState $s) => $s->user_id === $viewer->id ? $s->partner_id : $s->user_id)
            ->unique()
            ->values();
    }

    private function presence(User $user, User $viewer, Collection $blocked): array
    {
        return $blocked->contains($user->id)
            ? ['online' => false, 'last_seen_minutes' => null]
            : $user->presenceFor($viewer);
    }
}