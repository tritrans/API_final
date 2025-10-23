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
        Schema::table('comments', function (Blueprint $table) {
            $table->boolean('is_hidden')->default(false)->after('content');
            $table->text('hidden_reason')->nullable()->after('is_hidden');
            $table->unsignedBigInteger('hidden_by')->nullable()->after('hidden_reason');
            $table->timestamp('hidden_at')->nullable()->after('hidden_by');
            
            $table->foreign('hidden_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropForeign(['hidden_by']);
            $table->dropColumn(['is_hidden', 'hidden_reason', 'hidden_by', 'hidden_at']);
        });
    }
};