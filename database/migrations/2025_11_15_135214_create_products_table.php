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
        Schema::create('products', function (Blueprint $table) {
            $table->string('product_code', 20)->primary()->comment('商品代號');
            $table->string('brand', 30)->nullable()->comment('品牌');
            $table->string('name', 100)->comment('商品名稱');
            $table->string('spec', 100)->nullable()->comment('規格 (specification)');
            $table->decimal('factory_price', 9, 2)->nullable()->comment('廠價');
            $table->decimal('store_price', 9, 2)->nullable()->comment('店價');
            $table->decimal('selling_price', 9, 2)->nullable()->comment('售價');
            $table->decimal('gross_margin_pct', 8, 5)->nullable()->comment('店舖毛利率'); //excel 12345.678%進資料庫後會存成123.45678
            $table->string('shelf_life', 50)->nullable()->comment('保存期限');
            $table->string('category_code_1', 10)->nullable()->index()->comment('品號');
            $table->string('category_code_2', 10)->nullable()->index()->comment('群號');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
