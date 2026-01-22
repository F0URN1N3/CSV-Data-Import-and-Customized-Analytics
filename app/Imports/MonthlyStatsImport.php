<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\MonthlyStoreStat;
use App\Models\Category2digitMonthlySummary;
use App\Models\Category3digitMonthlySummary;
use App\Models\ProductMonthlySummary;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Illuminate\Support\Facades\Log;

class MonthlyStatsImport implements ToCollection, WithMultipleSheets
{
    // 暫存當前處理的代號與名稱
    private $currentCode = null;
    private $currentName = null;

    // 日期對應表 (Index => ['year' => 2025, 'month' => 5])
    private $dateColumnMap = [];

    /**
    *   1. 兩碼品群專用 Mapping
    *   Key 必須包含 "_合計"，用以對應原始資料中的合計行
    */
    private $fieldMap2Digit = [
        '實銷金額_合計' => 'sales_amount_total',
        '進貨數量_合計' => 'stock_in_quantity_total',
        '銷售數量_合計' => 'sales_quantity_total',
        '廢棄數量_合計' => 'waste_quantity_total',
        '退貨數量_合計' => 'return_quantity_total',
        '轉貨數量_合計' => 'transfer_quantity_total',
    ];

    /**
    *   2. 三碼品群 與 單品 專用 Mapping
    *   Key 完全不包含 "_合計"，也不包含 "%"
    *   這會自動過濾掉原始資料中帶有 "_合計" 的簡易版資料
    */
    private $fieldMapStandard = [
        // 店數相關
        '導入店數' => 'active_store_count',
        '進貨店數' => 'stock_in_store_count',
        '銷售店數' => 'sales_store_count',
        '導入店率' => 'active_store_rate_pct', // Excel 若為 "導入店率%"，會被清洗為 "導入店率"
        '進貨店率' => 'stock_in_store_rate_pct',

        // 實銷金額
        '實銷金額'          => 'sales_amount',
        '實銷金額_前年實績' => 'sales_amount_ly',
        '實銷金額_前年差'   => 'sales_amount_diff',
        '實銷金額_前年比'   => 'sales_amount_yoy_pct', // 清洗後對應
        '實銷金額_構成比'   => 'sales_amount_mix_pct',

        // 數量相關
        '進貨數量'          => 'stock_in_quantity',
        '進貨數量_前年實績' => 'stock_in_quantity_ly',

        '銷售數量'          => 'sales_quantity',
        '銷售數量_前年差'   => 'sales_quantity_diff',
        '銷售數量_前年比'   => 'sales_quantity_yoy_pct',

        '廢棄數量'          => 'waste_quantity',
        '廢棄數量_前年實績' => 'waste_quantity_ly',

        '退貨數量'          => 'return_quantity',
        '退貨數量_前年實績' => 'return_quantity_ly',

        '轉貨數量'          => 'transfer_quantity',
        '轉貨數量_前年實績' => 'transfer_quantity_ly',
    ];

    public function sheets(): array
    {
        // 只讀取第一個 Sheet
        return [0 => $this];
    }

    public function collection(Collection $rows)
    {
        /**  @var int|null */  //告訴 IDE，這個變數可以是整數或 null
        $headerRowIndex = null;

        //預設全店統計資料在第10行前結束(index從0開始)
        $globalStatsRowIndex = 9;


        // 1. 尋找標頭列 (含有日期格式如 114/05 的那一列)
        foreach ($rows as $index => $row) {
            if ($this->isHeaderRow($row)) {    //isHeaderRow('114/05'){return true;}
                $headerRowIndex = $index;
                $this->parseDateHeaders($row); //parseDateHeaders('114/05'){return dateColumnMap[5]=['year'=>2025,'month'=>5];}
                break;
            }
        }

        if ($headerRowIndex === null) {
            Log::warning("MonthlyStatsImport: 無法找到日期標頭列，停止匯入。");
            return;
        }

        // 2. 處理全店統計資料 (標頭列上方的全店數據)
        $this->processGlobalStats($rows, $globalStatsRowIndex);

        // 3. 處理主要資料 (從標頭列下一列開始)
        for ($i = $headerRowIndex + 1; $i < $rows->count(); $i++) {

            $rowCollection = $rows->get($i);
            // 安全檢查：若為空行或不存在，直接跳過
            if (!$rowCollection || $rowCollection->isEmpty()) {
                continue;
            }
            $row = $rowCollection->toArray();

            // 讀取 Column B (Index 1) 為代號
            $codeInRow = trim($row[1] ?? '');
            $nameInRow = trim($row[2] ?? '');

            // 處理合併儲存格：若有新代號則更新 Current，否則沿用
            if (!empty($codeInRow)) {
                $this->currentCode = $codeInRow;
                $this->currentName = $nameInRow;

                // 只有當代號長度為 2 或 3 時，維護 Categories 主檔
                $this->updateCategoryMaster($this->currentCode, $this->currentName);
            }

            // 若尚未取得有效代號，跳過
            if (empty($this->currentCode)) {
                continue;
            }

            // 讀取項目名稱 (Column D / Index 3)
            $rawMetricName = trim($row[3] ?? '');
            if (empty($rawMetricName)) {
                continue;
            }

            // 【關鍵步驟】清洗名稱：移除 "%" 和 ","，但保留 "_合計"
            // 這樣 "實銷金額_合計" 仍會保留，用於 2 碼匹配
            // "實銷金額_前年比%" 會變成 "實銷金額_前年比"，用於模糊匹配
            $cleanName = $this->cleanMetricName($rawMetricName);

            // 根據代號長度決定路由
            $len = strlen($this->currentCode);

            if ($len === 2) {
                // === 2 碼處理 (Category 2-digit) ===
                // 邏輯：必須完全匹配 fieldMap2Digit (Key 包含 "_合計")
                if (array_key_exists($cleanName, $this->fieldMap2Digit)) {
                    $dbField = $this->fieldMap2Digit[$cleanName];//如fieldMap2Digit[實銷金額_合計]則$dbField="sales_amount_total"
                    $this->saveTo2DigitTable($this->currentCode, $dbField, $row);
                }
            } elseif ($len === 3) {
                // === 3 碼處理 (Category 3-digit) ===
                // 邏輯：必須完全匹配 fieldMapStandard (Key 不含 "_合計")
                // 若 Excel 給的是 "實銷金額_合計"，因為 map 裡沒有這個 Key，會自動跳過 -> 完美過濾
                if (array_key_exists($cleanName, $this->fieldMapStandard)) {
                    $dbField = $this->fieldMapStandard[$cleanName];
                    $this->saveTo3DigitTable($this->currentCode, $dbField, $row);
                }
            } elseif ($len === 7) {
                // === 7 碼處理 (Product) ===
                // 邏輯同 3 碼
                if (array_key_exists($cleanName, $this->fieldMapStandard)) {
                    $dbField = $this->fieldMapStandard[$cleanName];
                    $this->saveToProductTable($this->currentCode, $dbField, $row);
                }
            }
        }
    }

    /**
     * 清洗指標名稱
     * 移除 "%" 和 "," (解決百分比可能消失的問題)
     * 保留 "_合計" (這是區分簡易版/詳細版資料的關鍵)
     */
    private function cleanMetricName($name)
    {
        return str_replace(['%', ','], '', $name);
    }

    private function updateCategoryMaster($code, $name)
    {
        $len = strlen($code);
        if ($len === 2 || $len === 3) {
            Category::updateOrCreate(
                ['category_code' => $code],
                [
                    'name'  => $name,
                    'level' => $len
                ]
            );
        }
    }

    // === 資料寫入函數 ===

    private function saveTo2DigitTable($code, $dbField, $row)
    {
        foreach ($this->dateColumnMap as $colIndex => $dateData) {
            $value = $this->parseNumber($row[$colIndex] ?? 0);
            Category2digitMonthlySummary::updateOrCreate(
                [
                    'year' => $dateData['year'],
                    'month' => $dateData['month'],
                    'category_code' => $code
                ],
                [$dbField => $value]
            );
        }
    }

    private function saveTo3DigitTable($code, $dbField, $row)
    {
        //ex: dateColumnMap[5]=['year'=>2025,'month'=>5];
        foreach ($this->dateColumnMap as $colIndex => $dateData) {
            $value = $this->parseNumber($row[$colIndex] ?? 0);
            Category3digitMonthlySummary::updateOrCreate(
                [
                    'year' => $dateData['year'],
                    'month' => $dateData['month'],
                    'category_code' => $code
                ],
                [$dbField => $value]
            );
        }
    }

    private function saveToProductTable($code, $dbField, $row)
    {
        foreach ($this->dateColumnMap as $colIndex => $dateData) {
            $value = $this->parseNumber($row[$colIndex] ?? 0);
            ProductMonthlySummary::updateOrCreate(
                [
                    'year' => $dateData['year'],
                    'month' => $dateData['month'],
                    'product_code' => $code
                ],
                [$dbField => $value]
            );
        }
    }

    // === 全店統計與輔助函數 ===

    private function processGlobalStats($rows, $globalStatsRowIndex)
    {
        // 搜尋標頭列上方的區域
        for ($i = 0; $i < $globalStatsRowIndex; $i++) {

            $rowCollection = $rows->get($i);
            // 安全檢查：若為空行或不存在，直接跳過
            if (!$rowCollection || $rowCollection->isEmpty()) {
                continue;
            }
            $row = $rowCollection->toArray();

        /**如果一行 Excel 數據在 Collection 中是這樣：
        *   $row = collect([
        *    '商品代號' => '3120123',
        *    '品牌' => '古道',
        *    '售價' => 25
        *   ]);
        *
        *   $row= $rows->get($i)->toArray() 變成： ['3120123', '古道', 25]
        *   implode(' ', $row) 變成： '3120123 古道 25'
        *   $rowStr 的值就是： '3120123 古道 25'
        */
            $rowStr = implode(' ', $row);

            $metricField = null;
            // 優先匹配長字串，避免 "天氣" 誤判 "去年同期天氣"
            if (str_contains($rowStr, '既存店數')) {
                $metricField = 'existing_store_count';
            } elseif (str_contains($rowStr, '去年同期天氣')) {
                $metricField = 'weather_ly';
            } elseif (str_contains($rowStr, '天氣')) {
                $metricField = 'weather';
            }

            if ($metricField) {
                foreach ($this->dateColumnMap as $colIndex => $dateData) {
                    $val = $row[$colIndex] ?? null;
                    if (is_null($val)) continue;

                    $finalVal = ($metricField === 'existing_store_count')
                        ? $this->parseNumber($val)
                        : trim($val);

                    MonthlyStoreStat::updateOrCreate(
                        ['year' => $dateData['year'], 'month' => $dateData['month']],
                        [$metricField => $finalVal]
                    );
                }
            }
        }
    }

    private function parseDateHeaders($row)
    {
        foreach ($row as $index => $cell) {
            $cellStr = trim((string)$cell);
            // 嚴格匹配民國年 "114/05"，自動忽略 "合計"
            if (preg_match('/^(\d{2,3})\/(\d{2})$/', $cellStr, $matches)) {
                $rocYear = (int)$matches[1];
                $month   = (int)$matches[2];
                $this->dateColumnMap[$index] = [
                    'year'  => $rocYear + 1911,
                    'month' => $month
                ];
            }
        }
    }

    private function isHeaderRow($row)
    {
        foreach ($row as $cell) {
            if (preg_match('/\d{2,3}\/\d{2}/', (string)$cell)) {
                return true;
            }
        }
        return false;
    }

    private function parseNumber($value)
    {
        if (is_null($value) || $value === '') return 0;

        $valueStr = (string)$value;

        // 檢查字串結尾是否為百分比符號，因為這是該函數讀取文字時會發生的情況
        $isPercentageString = str_ends_with(trim($valueStr), '%');

        // 清洗數值：移除 % 和 ,
        $cleanValue = str_replace(['%', ','], '', (string)$valueStr);

        $floatVal = is_numeric($cleanValue) ? floatval($cleanValue) : 0;

        // 如果原始資料帶有百分比符號，則除以 100 轉換為小數
        // 例如：'7.31%' -> '7.31' -> 7.31 -> 0.0731
        if ($isPercentageString) {
            return $floatVal / 100;
        }
        return $floatVal;
    }
}
