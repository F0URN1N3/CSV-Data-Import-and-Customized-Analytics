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
        Schema::create('category_2digit_monthly_summaries', function (Blueprint $table) {
            $table->smallInteger('year')->comment('年份');
            $table->tinyInteger('month')->comment('月份');
            $table->string('category_code', 10)->comment('兩碼品群代號 (FK)');

            // 6 個核心指標
            $table->bigInteger('sales_amount_total')->nullable()->comment('實銷金額_合計');
            $table->bigInteger('stock_in_quantity_total')->nullable()->comment('進貨數量_合計');
            $table->bigInteger('sales_quantity_total')->nullable()->comment('銷售數量_合計');
            $table->integer('waste_quantity_total')->nullable()->comment('廢棄數量_合計');
            $table->integer('return_quantity_total')->nullable()->comment('退貨數量_合計');
            $table->integer('transfer_quantity_total')->nullable()->comment('轉貨數量_合計');

            $table->timestamps();

            // 複合主鍵
            $table->primary(['year', 'month', 'category_code']);

            // 外鍵關聯
            $table->foreign('category_code')
                  ->references('category_code')
                  ->on('categories')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_2digit_monthly_summaries');
    }
};
