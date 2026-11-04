<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Réglages propres à chaque membre pour une discussion : suppression (pour lui seul) et blocage.
        Schema::create('chat_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('partner_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('cleared_at')->nullable(); // discussion supprimée par user_id : les messages plus anciens sont masqués pour lui
            $table->timestamp('blocked_at')->nullable(); // partner_id est bloqué par user_id
            $table->timestamps();
            $table->unique(['user_id', 'partner_id']);
        });

        // « text » ou « sticker » (dans ce cas, body contient l'identifiant de l'autocollant).
        Schema::table('messages', function (Blueprint $table) {
            $table->string('kind', 10)->default('text')->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('kind');
        });
        Schema::dropIfExists('chat_states');
    }
};