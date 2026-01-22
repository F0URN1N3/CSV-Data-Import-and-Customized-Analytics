<?php

namespace Tests\Feature;

use App\Imports\MonthlyStatsImport;
use App\Models\Product;
use App\Models\Category;
use App\Models\Category2digitMonthlySummary;
use App\Models\Category3digitMonthlySummary;
use App\Models\MonthlyStoreStat;
use App\Models\ProductMonthlySummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Maatwebsite\Excel\Facades\Excel;

class MonthlyStatsImportTest extends TestCase
{
    //每次測試跑完後會自動清空資料庫
    use RefreshDatabase;

    /**
     * 輔助函數：產生模擬的 Excel 資料結構
     * 結構包含：標頭列 (含有日期) + 傳入的數據列
     * 假設日期欄位在 Index 5
     */
    private function createImportData(array $dataRows, array $globalStatsRows = [])
    {
        // 1. 建立標頭列 (模擬 Excel 的第 5 或 第 6 列)
        // Index 5 是 114/05 (2025年5月)
        $headerRow = ['排行', 'Y軸項目', '', '項目', '合計', '114/05', '114/06', '114/07', '114/08', '114/09', '114/10'];

        // 2. 組合所有列：標頭列 + 全店統計列 + 數據列
        $allRows = array_merge([$headerRow], $globalStatsRows, $dataRows);

        // 3.真實的 Excel 套件讀進來時，每一列($row)其實是一個 Collection 物件
        // 所以這裡也要把每一列 array 轉成 collect()
        return collect($allRows)->map(function ($row) {
            return collect($row);
        });
    }

    /**
     * 測試 1: 全店統計資料 (天氣、店數) 是否正確匯入
     */
    public function test_imports_global_store_stats()
    {
        // 模擬兩列全店統計資料，放在標頭列之前
        // Index 4 對應 114/05 的數值
        $globalStatsRows = [
            ['Y軸', '代號', '名稱', '天氣', '', '雨轉晴', '雨轉晴', '雨轉晴', '晴', '晴', '晴'],
            ['', '', '', '去年同期天氣', '', '雨轉晴', '雨轉晴', '晴', '晴', '晴', '晴'],
            ['', '', '', '既存店數', '', "4,361", "4,388", "4,395", "4,405", "4,415", "4,422"],
        ];

        $data = $this->createImportData([], $globalStatsRows); // 沒有一般數據列

        $importer = new MonthlyStatsImport();
        $importer->collection($data);

        // 驗證 monthly_store_stats 表
        $this->assertDatabaseHas('monthly_store_stats', [
            'year' => 2025,
            'month' => 5,
            'weather' => '雨轉晴',
            'weather_ly' => '雨轉晴',
            'existing_store_count' => 4361,
        ]);
    }

    /**
     * 測試 2: 【兩碼品群】應該讀取帶有 "_合計" 的指標，並寫入 2digit 表
     */
    public function test_imports_2digit_category_with_total_columns()
    {
        $dataRows = [
            // Index 1=代號(31), Index 3=項目(實銷金額_合計), Index 4=數值(1000)
            [null, '31', '茶飲料', '實銷金額_合計', '6020', '1000', '1002', '1003', '1004', '1005', '1006'],
            [null, '32', '碳酸飲料', '廢棄數量_合計', '12020', '2000', '2002', '2003', '2004', '2005', '2006'],
        ];

        $data = $this->createImportData($dataRows);

        $importer = new MonthlyStatsImport();
        $importer->collection($data);

        // A. 驗證 category_2digit 表有資料
        $this->assertDatabaseHas('category_2digit_monthly_summaries', [
            'category_code' => '31',
            'year' => 2025,
            'month' => 5,
            'sales_amount_total' => 1000,
        ]);

        // B. 驗證 Categories 主檔有被建立/更新
        $this->assertDatabaseHas('categories', [
            'category_code' => '32',
            'name' => '碳酸飲料',
            'level' => 2,
        ]);
    }

    /**
     * 測試 3: 【三碼品群】應該 "忽略" 帶有 "_合計" 的指標 (這是簡易版資料)
     * 這就討論很久的過濾邏輯！
     */
    public function test_ignores_3digit_category_when_column_is_total()
    {
        $dataRows = [
            // 三碼代號 311，但給了 "實銷金額_合計" -> 程式應該要跳過
            [null, '311', '紅茶', '實銷金額_合計', '620', '100', '102', '103', '104', '105', '106'],
        ];

        $data = $this->createImportData($dataRows);

        $importer = new MonthlyStatsImport();
        $importer->collection($data);

        // 驗證：資料庫應該是空的，因為這筆資料被過濾掉了
        $this->assertDatabaseMissing('category_3digit_monthly_summaries', [
            'category_code' => '311',
        ]);
    }

    /**
     * 測試 4: 【三碼品群】應該讀取 "不帶合計" 的詳細指標
     */
    public function test_imports_3digit_category_with_standard_columns()
    {
        $rows = [
            // 三碼代號 311，給了 "實銷金額" (詳盡版)
            [null, '312', '綠茶', '銷售數量_前年差', '105', '15', '16', '17', '18', '19', '20'],
        ];

        $data = $this->createImportData($rows);

        $importer = new MonthlyStatsImport();
        $importer->collection($data);

        // 驗證 category_3digit 表
        $this->assertDatabaseHas('category_3digit_monthly_summaries', [
            'category_code' => '312',
            'year' => 2025,
            'month' => 8,
            'sales_quantity_diff' => 18,
        ]);

        // 驗證 Categories 主檔 (Level 3)
        $this->assertDatabaseHas('categories', [
            'category_code' => '312',
            'level' => 3,
        ]);
    }

    /**
     * 測試 5: 【七碼單品】應該匯入 Product 表，並正確清洗 "%" 符號
     */
    public function test_imports_product_and_cleans_percentage_symbol()
    {

        //先建立一個虛假的商品
        Product::create([
        'product_code' => '3110001',
        'name' => '某某紅茶',
        ]);

        $rows = [
            // 七碼代號，項目名稱帶有 "%" 和 ","
            ['77', '3110001', '某某紅茶', '實銷金額_前年比%', '600.66%', '105.5%'],
        ];

        $data = $this->createImportData($rows);

        $importer = new MonthlyStatsImport();
        $importer->collection($data);

        // 驗證 product_monthly_summaries 表
        $this->assertDatabaseHas('product_monthly_summaries', [
            'product_code' => '3110001',
            'year' => 2025,
            'month' => 5,
            // 驗證 % 被去除了，且數值正確
            'sales_amount_yoy_pct' => 105.5,
        ]);
    }
}
