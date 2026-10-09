<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // null = donateur non connecté
            $table->string('donor_name');
            $table->string('donor_phone', 20)->nullable();
            $table->string('donor_email')->nullable();
            $table->unsignedInteger('amount'); // FCFA
            $table->string('status', 20)->default('pending')->index(); // pending | paid | failed
            $table->string('provider', 30)->nullable();
            $table->string('reference')->unique();
            $table->string('provider_reference')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};