<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table standard des notifications de Laravel.
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        // « notified_at » : ce contenu a déjà donné lieu à une notification (évite les doublons).
        foreach (['publications', 'projects', 'fundraisers'] as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'notified_at')) {
                Schema::table($table, fn (Blueprint $t) => $t->timestamp('notified_at')->nullable());

                // Le contenu déjà en ligne ne déclenche pas de notification.
                DB::table($table)->update(['notified_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        foreach (['publications', 'projects', 'fundraisers'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'notified_at')) {
                Schema::table($table, fn (Blueprint $t) => $t->dropColumn('notified_at'));
            }
        }

        Schema::dropIfExists('notifications');
    }
};