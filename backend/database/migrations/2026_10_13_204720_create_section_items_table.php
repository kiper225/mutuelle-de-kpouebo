<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('section_items', function (Blueprint $table) {
            $table->id();
            $table->string('section', 20); // bureau | axes | valeurs
            $table->string('title', 150);
            $table->string('subtitle', 150)->nullable();
            $table->text('text')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['section', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('section_items');
    }
};