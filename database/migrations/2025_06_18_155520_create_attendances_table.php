<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('work_date');
            $table->datetime('clock_in')->nullable(); // 出勤
            $table->datetime('clock_out')->nullable(); // 退勤
            $table->string('work_status')->default('off'); // デフォルト勤務ステータス
            $table->string('reason')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // 複合ユニークキー
            $table->unique(['user_id', 'work_date']);

            // 外部キー制約
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
