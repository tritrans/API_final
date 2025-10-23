<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movie_people', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('movie_id');
            $table->unsignedBigInteger('person_id');
            $table->enum('role', ['actor', 'director', 'writer', 'producer'])->default('actor');
            $table->string('character_name')->nullable();
            $table->unsignedInteger('billing_order')->nullable();
            $table->timestamps();

            $table->unique(['movie_id', 'person_id', 'role'], 'uniq_movie_person_role');
            $table->foreign('movie_id')->references('id')->on('movies')->onDelete('cascade');
            $table->foreign('person_id')->references('id')->on('people')->onDelete('cascade');
            $table->index(['movie_id', 'role', 'billing_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movie_people');
    }
};


