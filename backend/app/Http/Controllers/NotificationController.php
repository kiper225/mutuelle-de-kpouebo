<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

class NotificationController extends Controller
{
    /** Notifications du membre, nombre de non lues, et résumé des messages non lus (pour les bulles d'alerte). */
    public function index(Request $request)
    {
        $user = $request->user();
        $limit = min(50, max(1, (int) $request->query('limit', 20)));

        $items = $user->notifications()->limit($limit)->get()->map(fn (DatabaseNotification $n) => [
            'id' => $n->id,
            'kind' => $n->data['kind'] ?? $n->type,
            'title' => $n->data['title'] ?? '',
            'body' => $n->data['body'] ?? '',
            'url' => $n->data['url'] ?? null,
            'read' => $n->read_at !== null,
            'created_at' => $n->created_at,
        ]);

        $unreadMessages = Message::where('recipient_id', $user->id)->whereNull('read_at');
        $latest = (clone $unreadMessages)->with('sender:id,name')->latest('id')->first();

        return [
            'unread' => $user->unreadNotifications()->count(),
            'items' => $items,
            'messages' => [
                'unread' => $unreadMessages->count(),
                'latest' => $latest ? [
                    'id' => $latest->id,
                    'sender_id' => $latest->sender_id,
                    'name' => $latest->sender->name,
                    'preview' => $latest->kind === 'sticker' ? 'Autocollant' : Str::limit($latest->body, 80),
                ] : null,
            ],
        ];
    }

    /** Marque une notification comme lue (uniquement si elle appartient au membre connecté). */
    public function read(Request $request, string $id)
    {
        $request->user()->notifications()->whereKey($id)->whereNull('read_at')->update(['read_at' => now()]);

        return response()->noContent();
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->noContent();
    }
}