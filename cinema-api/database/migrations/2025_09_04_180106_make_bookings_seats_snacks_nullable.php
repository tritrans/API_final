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
        if (!Schema::hasTable('bookings')) {
            return;
        }
        Schema::table('bookings', function (Blueprint $table) {
            // Make seats and snacks columns nullable if they exist
            if (Schema::hasColumn('bookings', 'seats')) {
                $table->json('seats')->nullable()->change();
            }
            if (Schema::hasColumn('bookings', 'snacks')) {
                $table->json('snacks')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('bookings')) {
            return;
        }
        Schema::table('bookings', function (Blueprint $table) {
            // Revert seats and snacks columns to not nullable if they exist
            if (Schema::hasColumn('bookings', 'seats')) {
                $table->json('seats')->nullable(false)->change();
            }
            if (Schema::hasColumn('bookings', 'snacks')) {
                $table->json('snacks')->nullable(false)->change();
            }
        });
    }
};
