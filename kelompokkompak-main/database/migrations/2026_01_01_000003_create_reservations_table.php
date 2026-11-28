<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('court_id')->constrained()->restrictOnDelete();
            $table->enum('type', ['booking', 'maintenance'])->default('booking');
            $table->date('booking_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedTinyInteger('duration_hours');
            $table->unsignedTinyInteger('overtime_hours')->default(0);
            $table->unsignedInteger('price_per_hour')->default(0);
            $table->unsignedInteger('total_price')->default(0);
            $table->unsignedInteger('penalty')->default(0);
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed', 'cancelled'])->default('pending');
            $table->string('payment_proof')->nullable();
            $table->string('notes', 500)->nullable();
            $table->string('admin_note', 500)->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['court_id', 'booking_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
