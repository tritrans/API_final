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
            // Xóa cột seats nếu tồn tại
            if (Schema::hasColumn('bookings', 'seats')) {
                $table->dropColumn('seats');
            }
            
            // Xóa cột snacks nếu tồn tại
            if (Schema::hasColumn('bookings', 'snacks')) {
                $table->dropColumn('snacks');
            }
            
            // Thêm các cột cần thiết nếu chưa có
            if (!Schema::hasColumn('bookings', 'booking_id')) {
                $table->string('booking_id')->unique()->after('id');
            }
            
            if (!Schema::hasColumn('bookings', 'user_id')) {
                $table->foreignId('user_id')->constrained()->onDelete('cascade')->after('booking_id');
            }
            
            if (!Schema::hasColumn('bookings', 'showtime_id')) {
                $table->foreignId('showtime_id')->constrained('schedules')->onDelete('cascade')->after('user_id');
            }
            
            if (!Schema::hasColumn('bookings', 'total_price')) {
                $table->decimal('total_price', 10, 2)->after('showtime_id');
            }
            
            if (!Schema::hasColumn('bookings', 'status')) {
                $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('confirmed')->after('total_price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // no-op
    }
};
