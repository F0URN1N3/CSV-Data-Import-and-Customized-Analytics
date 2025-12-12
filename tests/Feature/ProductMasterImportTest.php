<?php

namespace Tests\Feature;

use App\Imports\ProductMasterImport;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProductMasterImportTest extends TestCase
{
    //每次測試跑完後會自動清空資料庫
    use RefreshDatabase;

    /**
     * 測試 1: 正常的商品新增功能
     */
    public function test_can_import_and_create_new_product()
    {
        // 1. 準備一筆模擬 Excel 的「列 (Row)」資料
        // 注意：這裡的 Key 必須跟你在 ProductMasterImport.php 裡寫的一模一樣
        $row = [
            '商品代號' => 'TEST001',
            '品牌'     => '測試品牌',
            '商品名稱' => '測試綠茶',
            '規格'     => '600ml',
            '廠價'     => '10.15',
            '店價'     => '20.05',
            '售價'     => '25',
            '店舖毛利率' => '39.6%',
            '保存期限' => '12months',
            '品號'     => '31',
            '群號'     => '2',
        ];

        // 2. 實例化你的 Import 類別
        $importer = new ProductMasterImport();

        // 3. 直接呼叫 model 方法，把假資料餵進去
        $importer->model($row);

        // 4. 驗證 (Assert)：檢查資料庫 products 表是否真的多了一筆資料
        $this->assertDatabaseHas('products', [
            'product_code' => 'TEST001',
            'name'         => '測試綠茶',
            'brand'        => '測試品牌',
            'selling_price' => 25,
        ]);
    }

    /**
     * 測試 2: 重複匯入時應該更新資料 (Update)
     */
    public function test_can_update_existing_product()
    {
        // 1. 先在資料庫建立一筆舊資料
        Product::create([
            'product_code' => 'TEST002',
            'name'         => '舊名稱',
            'selling_price' => 100,
        ]);

        // 2. 準備一筆「同代號」但在 Excel 中數值變更的資料
        $row = [
            '商品代號' => 'TEST002', // 代號相同
            '品牌'     => '新品牌',
            '商品名稱' => '新名稱',   // 名稱變了
            '規格'     => '600ml',
            '廠價'     => '10',
            '店價'     => '20',
            '售價'     => '200',     // 價格變了
        ];

        // 3. 執行匯入
        $importer = new ProductMasterImport();
        $importer->model($row);

        // 4. 驗證：
        // A. 確認資料庫裡還是只有 1 筆 TEST002 (沒有重複新增)
        $this->assertDatabaseCount('products', 1);

        // B. 確認資料內容已經變成新的了
        $this->assertDatabaseHas('products', [
            'product_code' => 'TEST002',
            'name'         => '新名稱',
            'selling_price' => 200,
        ]);
    }

    /**
     * 測試 3: 數值清洗功能 (移除逗號)
     */
    public function test_cleans_number_format_removes_commas()
    {
        // 1. 準備帶有千分位逗號的資料
        $row = [
            '商品代號' => 'TEST003',
            '商品名稱' => '高價商品',
            '廠價'     => '1,000', // Excel 裡常見的格式
            '店價'     => '2,000',
            '售價'     => '2,500',
        ];

        // 2. 執行匯入
        $importer = new ProductMasterImport();
        $importer->model($row);

        // 3. 驗證：資料庫裡應該存的是純數字 2500
        $this->assertDatabaseHas('products', [
            'product_code' => 'TEST003',
            'factory_price' => 1000,
            'store_price'   => 2000,
            'selling_price' => 2500,
        ]);
    }
}
