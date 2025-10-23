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
        // Thay đổi cột role từ string thành foreign key đến bảng roles
        Schema::table('users', function (Blueprint $table) {
            // Xóa cột role cũ
            $table->dropColumn('role');
        });
        
        Schema::table('users', function (Blueprint $table) {
            // Thêm cột role_id mới
            $table->foreignId('role_id')->nullable()->constrained('roles')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Xóa foreign key constraint
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });
        
        Schema::table('users', function (Blueprint $table) {
            // Khôi phục cột role cũ
            $table->string('role')->default('user');
        });
    }
};
