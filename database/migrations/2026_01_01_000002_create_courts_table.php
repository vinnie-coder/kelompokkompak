<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sport_category_id')->constrained()->restrictOnDelete();
            $table->string('name', 100)->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('price_per_hour');
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('total_hours_used')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courts');
    }
};
