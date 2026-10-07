<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $phone = env('ADMIN_PHONE');
        $password = env('ADMIN_PASSWORD');

        if (! $phone || ! $password) {
            $this->command?->warn('ADMIN_PHONE et ADMIN_PASSWORD doivent être définis dans .env : aucun admin créé.');

            return;
        }

        $admin = User::firstOrNew(['phone' => $phone]);
        $admin->fill([
            'name' => env('ADMIN_NAME', 'Administrateur'),
            'password' => $password,
        ]);
        $admin->forceFill(['role' => User::ROLE_ADMIN, 'status' => User::STATUS_ACTIVE])->save();
    }
}
