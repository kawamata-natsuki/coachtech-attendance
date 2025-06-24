<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('correction_break_times', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('correction_request_id');
            $table->unsignedBigInteger('break_time_id')->nullable();
            $table->datetime('requested_break_start')->nullable();
            $table->datetime('requested_break_end')->nullable();
            $table->datetime('original_break_start')->nullable();
            $table->datetime('original_break_end')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // 外部キー制約
            $table->foreign('correction_request_id')->references('id')->on('correction_requests')->onDelete('cascade');
            $table->foreign('break_time_id')->references('id')->on('break_times')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correction_break_times');
    }
};
