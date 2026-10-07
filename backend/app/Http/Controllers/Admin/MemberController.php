<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        return User::members()
            ->when($request->query('status'), fn ($query, $s) => $query->where('status', $s))
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.addcslashes($q, '%_\\').'%';
                $query->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('phone', 'like', $like));
            })
            ->latest()
            ->paginate(20);
    }

    public function approve(User $user)
    {
        $this->ensureMember($user);
        $user->forceFill(['status' => User::STATUS_ACTIVE])->save();

        return $user;
    }

    public function suspend(User $user)
    {
        $this->ensureMember($user);
        $user->forceFill(['status' => User::STATUS_SUSPENDED])->save();
        $user->tokens()->delete(); // déconnecte immédiatement le membre

        return $user;
    }

    private function ensureMember(User $user): void
    {
        abort_if($user->isAdmin(), 422, 'Action impossible sur un administrateur.');
    }
}
