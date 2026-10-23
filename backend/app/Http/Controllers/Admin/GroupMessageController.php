<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cotisation;
use App\Models\GroupMessage;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Messages groupés de l'administrateur : un message privé est créé pour chaque destinataire. */
class GroupMessageController extends Controller
{
    /** Historique, avec le nombre de destinataires qui ont lu le message. */
    public function index()
    {
        return GroupMessage::with('sender:id,name')
            ->withCount(['messages as read_count' => fn ($q) => $q->whereNotNull('read_at')])
            ->latest()
            ->paginate(15);
    }

    /** Nombre de destinataires d'un public prédéfini, affiché avant l'envoi. */
    public function audience(Request $request)
    {
        $audience = $request->validate(['audience' => ['required', Rule::in(['all', 'overdue'])]])['audience'];

        return ['count' => $this->recipientIds($audience, [], $request->user())->count()];
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'audience' => ['required', Rule::in(['all', 'overdue', 'selected'])],
            'member_ids' => ['required_if:audience,selected', 'array', 'max:500'],
            'member_ids.*' => ['integer'],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $admin = $request->user();
        $ids = $this->recipientIds($data['audience'], $data['member_ids'] ?? [], $admin);

        abort_if($ids->isEmpty(), 422, 'Aucun destinataire pour cette sélection.');

        $group = DB::transaction(function () use ($data, $admin, $ids) {
            $group = new GroupMessage(['body' => $data['body']]);
            $group->forceFill([
                'sender_id' => $admin->id,
                'audience' => $data['audience'],
                'recipients_count' => $ids->count(),
            ])->save();

            $now = now();
            foreach ($ids->chunk(500) as $chunk) {
                Message::insert($chunk->map(fn ($id) => [
                    'sender_id' => $admin->id,
                    'recipient_id' => $id,
                    'group_message_id' => $group->id,
                    'body' => $data['body'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all());
            }

            return $group;
        });

        return response()->json($group->load('sender:id,name'), 201);
    }

    /**
     * Seuls les membres actifs reçoivent un message groupé (jamais les comptes en attente, suspendus ou administrateurs).
     *
     * @return Collection<int, int>
     */
    private function recipientIds(string $audience, array $selected, User $admin): Collection
    {
        $query = User::members()
            ->where('status', User::STATUS_ACTIVE)
            ->where('id', '!=', $admin->id);

        if ($audience === 'selected') {
            $query->whereIn('id', $selected);
        } elseif ($audience === 'overdue') {
            $period = now()->format('Y-m');
            $query->whereDoesntHave('cotisations', fn ($q) => $q
                ->where('period', $period)
                ->where('status', Cotisation::STATUS_PAID));
        }

        return $query->pluck('id');
    }
}