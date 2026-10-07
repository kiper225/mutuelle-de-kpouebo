<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotisations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('period', 7);                 // ex. 2026-10
            $table->unsignedInteger('amount');           // en FCFA (XOF), sans décimales
            $table->string('status', 20)->default('pending')->index(); // pending | paid | failed
            $table->string('provider', 30)->nullable();  // orange_money, mtn_momo, moov_money, wave, card, cash
            $table->string('reference')->unique();       // référence interne envoyée à la passerelle
            $table->string('provider_reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete(); // admin pour un paiement en espèces
            $table->timestamps();
            $table->unique(['user_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotisations');
    }
};
