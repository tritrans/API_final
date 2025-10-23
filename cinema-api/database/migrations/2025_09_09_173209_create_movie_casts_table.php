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
        Schema::create('movie_casts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->constrained()->onDelete('cascade');
            $table->foreignId('cast_id')->constrained()->onDelete('cascade');
            $table->string('character_name')->nullable(); // Tên nhân vật trong phim
            $table->timestamps();
            
            // Đảm bảo không có duplicate movie-cast combination
            $table->unique(['movie_id', 'cast_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movie_casts');
    }
};
