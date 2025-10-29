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
        Schema::table('reviews', function (Blueprint $table) {
            // First, add the parent_review_id column
            $table->unsignedBigInteger('parent_review_id')->nullable()->after('movie_id');
            $table->foreign('parent_review_id')->references('id')->on('reviews')->onDelete('cascade');
            
            // Drop foreign key constraints that might reference the unique index
            $table->dropForeign(['user_id']);
            $table->dropForeign(['movie_id']);
            
            // Drop the existing unique constraint
            $table->dropUnique(['user_id', 'movie_id']);
            
            // Add new unique constraint that allows multiple reviews per user per movie
            // but only one main review (parent_review_id is null)
            $table->unique(['user_id', 'movie_id', 'parent_review_id'], 'reviews_user_movie_parent_unique');
            
            // Re-add foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('movie_id')->references('id')->on('movies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            // Drop the new unique constraint
            $table->dropUnique('reviews_user_movie_parent_unique');
            
            // Restore the original unique constraint
            $table->unique(['user_id', 'movie_id']);
        });
    }
};
