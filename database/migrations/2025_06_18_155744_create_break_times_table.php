<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('break_times', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attendance_id');
            $table->datetime('break_start');
            $table->datetime('break_end')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // 外部キー制約
            $table->foreign('attendance_id')->references('id')->on('attendances')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('break_times');
    }
};
