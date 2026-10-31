<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('section_items', function (Blueprint $table) {
            $table->string('icon', 30)->nullable()->after('subtitle'); // clé d'une icône prédéfinie (voir SectionItem::ICONS)
        });
    }

    public function down(): void
    {
        Schema::table('section_items', function (Blueprint $table) {
            $table->dropColumn('icon');
        });
    }
};