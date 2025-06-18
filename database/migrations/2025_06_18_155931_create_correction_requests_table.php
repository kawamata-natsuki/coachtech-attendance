<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('correction_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attendance_id');
            $table->unsignedBigInteger('user_id'); // 申請者
            $table->date('work_date');
            $table->datetime('requested_clock_in');
            $table->datetime('requested_clock_out');
            $table->datetime('original_clock_in');
            $table->datetime('original_clock_out');
            $table->string('reason');

            $table->datetime('approved_at')->nullable();
            $table->unsignedBigInteger('approver_id')->nullable(); // 承認者
            $table->string('approval_status')->default('pending');

            $table->softDeletes();
            $table->timestamps();

            // 外部キー制約
            $table->foreign('attendance_id')->references('id')->on('attendances')->onDelete('restrict');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('approver_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correction_requests');
    }
};
