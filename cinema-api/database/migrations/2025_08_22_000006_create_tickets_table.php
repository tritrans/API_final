<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('user_email');
            $table->foreignId('movie_id')->constrained()->onDelete('cascade');
            $table->string('movie_title');
            $table->string('poster_url');
            $table->foreignId('schedule_id')->constrained()->onDelete('cascade');
            $table->json('seats');
            $table->decimal('total_amount', 10, 2);
            $table->dateTime('date_time');
            $table->foreignId('theater_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['booked', 'paid', 'cancelled'])->default('booked');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
