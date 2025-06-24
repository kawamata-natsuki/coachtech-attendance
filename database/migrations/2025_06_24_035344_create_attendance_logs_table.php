<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();

            // 勤怠データと管理者
            $table->foreignId('attendance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('updated_by_admin_id')->constrained('admins')->cascadeOnDelete();

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
            $table->text('before_reason')->nullable();
            $table->text('after_reason')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
