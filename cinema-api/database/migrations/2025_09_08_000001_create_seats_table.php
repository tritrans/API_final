<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('theater_id');
            $table->string('row_label', 10);
            $table->unsignedInteger('seat_number');
            $table->timestamps();

            $table->unique(['theater_id', 'row_label', 'seat_number'], 'uniq_theater_row_num');
            $table->foreign('theater_id')->references('id')->on('theaters')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};


