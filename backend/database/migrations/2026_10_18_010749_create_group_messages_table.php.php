<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->string('audience', 20); // all | overdue | selected
            $table->text('body');
            $table->unsignedInteger('recipients_count')->default(0);
            $table->timestamps();
        });

        // Chaque destinataire reçoit un message privé rattaché au message groupé.
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('group_message_id')->nullable()->after('recipient_id')
                ->constrained('group_messages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('group_message_id');
        });
        Schema::dropIfExists('group_messages');
    }
};