<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attendance_id');
            $table->unsignedBigInteger('updated_by_admin_id');

            // 修正種別（直接修正か、申請承認による修正か）
            $table->string('action_type')->default('direct'); // 'direct' or 'approval'

            // 出退勤の修正前後
            $table->dateTime('before_clock_in')->nullable();
            $table->dateTime('before_clock_out')->nullable();
            $table->dateTime('after_clock_in')->nullable();
            $table->dateTime('after_clock_out')->nullable();

            // 休憩時間（配列形式）
            $table->json('before_breaks')->nullable();
            $table->json('after_breaks')->nullable();

            // 備考欄（修正理由）
            $table->string('before_reason')->nullable();
            $table->string('after_reason')->nullable();

            $table->timestamps();

            // 外部キー制約
            $table->foreign('attendance_id')->references('id')->on('attendances')->onDelete('restrict');
            $table->foreign('updated_by_admin_id')->references('id')->on('admins')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
