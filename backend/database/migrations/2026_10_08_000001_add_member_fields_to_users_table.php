<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change(); // l'email est facultatif, le téléphone est l'identifiant principal
            $table->string('last_name')->nullable()->after('name');
            $table->string('first_names')->nullable()->after('last_name');
            $table->string('phone', 20)->nullable()->unique()->after('email');
            $table->date('birth_date')->nullable();
            $table->string('profession')->nullable();
            $table->string('residence')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('role', 20)->default('member')->index();
            $table->string('status', 20)->default('pending')->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->dropIndex(['role']);
            $table->dropIndex(['status']);
            $table->dropColumn([
                'last_name', 'first_names', 'phone', 'birth_date',
                'profession', 'residence', 'photo_path', 'role', 'status',
            ]);
        });
    }
};
