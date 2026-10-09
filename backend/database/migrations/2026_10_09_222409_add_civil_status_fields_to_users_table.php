<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('birth_place')->nullable();
            $table->unsignedSmallInteger('children_count')->nullable();
            $table->string('marital_status', 20)->nullable();
            $table->string('mutuelle_role', 100)->nullable();
            $table->string('member_number', 30)->nullable()->unique();
            $table->timestamp('joined_at')->nullable();
        });

        // Les membres déjà actifs reçoivent un numéro.
        $prefix = config('mutuelle.member_prefix', 'MBR');
        DB::table('users')
            ->where('role', 'member')->where('status', 'active')->whereNull('member_number')
            ->orderBy('id')->get()
            ->each(function ($user) use ($prefix) {
                DB::table('users')->where('id', $user->id)->update([
                    'member_number' => sprintf('%s-%s-%04d', $prefix, date('Y', strtotime((string) $user->created_at)), $user->id),
                    'joined_at' => $user->created_at,
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['member_number']);
            $table->dropColumn(['birth_place', 'children_count', 'marital_status', 'mutuelle_role', 'member_number', 'joined_at']);
        });
    }
};