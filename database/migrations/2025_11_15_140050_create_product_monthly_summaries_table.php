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
        Schema::create('product_monthly_summaries', function (Blueprint $table) {
            $table->smallInteger('year')->comment('年份');
            $table->tinyInteger('month')->comment('月份');
            $table->string('product_code', 20)->comment('商品代號 (FK)');
            $table->string('parent_category_code', 10)->nullable()->index()->comment('所屬品群代號 (FK)');

            // 店數相關
            $table->integer('active_store_count')->nullable()->comment('導入店數');
            $table->integer('stock_in_store_count')->nullable()->comment('進貨店數');
            $table->integer('sales_store_count')->nullable()->comment('銷售店數');
            $table->decimal('active_store_rate_pct', 8, 2)->nullable()->comment('導入店率%');
            $table->decimal('stock_in_store_rate_pct', 8, 2)->nullable()->comment('進貨店率');

            // 實銷金額
            $table->bigInteger('sales_amount')->nullable()->comment('實銷金額');
            $table->bigInteger('sales_amount_ly')->nullable()->comment('實銷金額_前年實績');
            $table->bigInteger('sales_amount_diff')->nullable()->comment('實銷金額_前年差');
            $table->decimal('sales_amount_yoy_pct', 10, 2)->nullable()->comment('實銷金額_前年比%');
            $table->decimal('sales_amount_mix_pct', 8, 2)->nullable()->comment('實銷金額_構成比%');

            // 數量
            $table->bigInteger('stock_in_quantity')->nullable()->comment('進貨數量');
            $table->bigInteger('stock_in_quantity_ly')->nullable()->comment('進貨數量_前年實績');
            $table->bigInteger('sales_quantity')->nullable()->comment('銷售數量');
            $table->bigInteger('sales_quantity_diff')->nullable()->comment('銷售數量_前年差');
            $table->decimal('sales_quantity_yoy_pct', 10, 2)->nullable()->comment('銷售數量_前年比%');
            $table->integer('waste_quantity')->nullable()->comment('廢棄數量');
            $table->integer('waste_quantity_ly')->nullable()->comment('廢棄數量_前年實績');
            $table->integer('return_quantity')->nullable()->comment('退貨數量');
            $table->integer('return_quantity_ly')->nullable()->comment('退貨數量_前年實績');
            $table->integer('transfer_quantity')->nullable()->comment('轉貨數量');
            $table->integer('transfer_quantity_ly')->nullable()->comment('轉貨數量_前年實績');

            $table->timestamps();

            // 複合主鍵
            $table->primary(['year', 'month', 'product_code']);

            // 外鍵關聯
            $table->foreign('product_code')
                  ->references('product_code')
                  ->on('products')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            $table->foreign('parent_category_code')
                  ->references('category_code')
                  ->on('categories')
                  ->onDelete('set null') // 如果品群被刪除，單品資料保留，但關聯設為 NULL
                  ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_monthly_summaries');
    }
};
