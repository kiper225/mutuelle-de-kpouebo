<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->string('summary', 300)->nullable();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->string('status', 20)->default('en_cours')->index(); // en_cours | realise | a_venir
            $table->unsignedTinyInteger('progress')->nullable();        // avancement en %, pour un chantier en cours
            $table->string('location', 150)->nullable();
            $table->unsignedInteger('budget')->nullable();              // en FCFA
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->foreignId('fundraiser_id')->nullable()->constrained()->nullOnDelete(); // levée de fonds liée
            $table->unsignedInteger('position')->default(0)->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};