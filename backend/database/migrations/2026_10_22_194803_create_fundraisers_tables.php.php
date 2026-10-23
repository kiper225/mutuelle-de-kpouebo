<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fundraisers', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->string('summary', 300)->nullable();   // résumé en quelques mots (cartes)
            $table->text('description')->nullable();      // explication de l'activité
            $table->string('image_path')->nullable();
            $table->unsignedInteger('goal_amount')->nullable();      // objectif en FCFA
            $table->unsignedInteger('suggested_amount')->nullable(); // montant conseillé en FCFA
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fundraiser_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->string('status', 20)->default('pending')->index(); // pending | paid | failed
            $table->string('provider', 30)->nullable();
            $table->string('reference')->unique();
            $table->string('provider_reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete(); // renseigné = enregistré par le bureau
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contributions');
        Schema::dropIfExists('fundraisers');
    }
};
