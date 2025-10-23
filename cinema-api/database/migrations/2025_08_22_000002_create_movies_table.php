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
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('title_vi');
            $table->text('description');
            $table->text('description_vi');
            $table->string('poster');
            $table->string('backdrop')->nullable();
            $table->string('trailer')->nullable();
            $table->dateTime('release_date');
            $table->integer('duration');
            $table->decimal('rating', 3, 1)->default(0);
            $table->string('country');
            $table->string('language');
            $table->string('director');
            $table->json('cast');
            $table->string('slug')->unique();
            $table->boolean('featured')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
