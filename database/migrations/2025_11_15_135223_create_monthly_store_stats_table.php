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
        Schema::create('monthly_store_stats', function (Blueprint $table) {
            $table->smallInteger('year')->comment('年份');
            $table->tinyInteger('month')->comment('月份');
            $table->string('weather', 20)->nullable()->comment('天氣');
            $table->string('weather_ly', 20)->nullable()->comment('去年同期天氣');
            $table->integer('existing_store_count')->nullable()->comment('既存店數');
            $table->timestamps();

            // 建立 (year, month) 的複合主鍵
            $table->primary(['year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_store_stats');
    }
};
