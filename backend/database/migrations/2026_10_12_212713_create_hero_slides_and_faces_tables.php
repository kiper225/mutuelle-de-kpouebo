<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_slides', function (Blueprint $table) {
            $table->id();
            $table->string('eyebrow', 60)->nullable();
            $table->string('title', 150);
            $table->string('text', 400)->nullable();
            $table->string('image_path')->nullable();
            $table->string('cta_label', 40)->nullable();
            $table->string('cta_to', 200)->nullable();
            $table->string('cta2_label', 40)->nullable();
            $table->string('cta2_to', 200)->nullable();
            $table->unsignedInteger('position')->default(0)->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('faces', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('role', 150)->nullable();
            $table->text('text')->nullable();
            $table->string('photo_path')->nullable();
            $table->unsignedInteger('position')->default(0)->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faces');
        Schema::dropIfExists('hero_slides');
    }
};