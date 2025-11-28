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
    // 暫存當前處理的代號與名稱 (處理合併儲存格用)
    private $currentCode = null;
    private $currentName = null;

    // 日期對應表 (Index => ['year' => 2025, 'month' => 5])
    private $dateColumnMap = [];

    // 定義統計項目的欄位對應 (通用於所有 Summary Table)
    private $fieldMap = [
        // 核心指標 (兩碼/三碼/單品共用)
        '實銷金額' => 'sales_amount', // 自動匹配 "實銷金額_合計"
        '進貨數量' => 'stock_in_quantity',
        '銷售數量' => 'sales_quantity',
        '廢棄數量' => 'waste_quantity',
        '退貨數量' => 'return_quantity',
        '轉貨數量' => 'transfer_quantity',

        // 僅三碼/單品有的指標 (店數與佔比)
        '導入店數' => 'active_store_count',
        '進貨店數' => 'stock_in_store_count',
        '銷售店數' => 'sales_store_count',
        '導入店率' => 'active_store_rate_pct',
        '進貨店率' => 'stock_in_store_rate_pct',

        // 前年比與差異
        '實銷金額_前年實績' => 'sales_amount_ly',
        '實銷金額_前年差'   => 'sales_amount_diff',
        '實銷金額_前年比'   => 'sales_amount_yoy_pct',
        '實銷金額_構成比'   => 'sales_amount_mix_pct',

        '進貨數量_前年實績' => 'stock_in_quantity_ly',

        '銷售數量_前年差'   => 'sales_quantity_diff',
        '銷售數量_前年比'   => 'sales_quantity_yoy_pct',

        '廢棄數量_前年實績' => 'waste_quantity_ly',
        '退貨數量_前年實績' => 'return_quantity_ly',
        '轉貨數量_前年實績' => 'transfer_quantity_ly',
    ];

    public function sheets(): array
    {
        return [0 => $this];
    }

    public function collection(Collection $rows)
    {
        $headerRowIndex = null;

        // 1. 尋找標頭列 (含有日期的那一列)
        foreach ($rows as $index => $row) {
            if ($this->isHeaderRow($row)) {
                $headerRowIndex = $index;
                $this->parseDateHeaders($row);
                break;
            }
        }

        if ($headerRowIndex === null) {
            Log::warning("MonthlyStatsImport: 無法找到日期標頭列，停止匯入。");
            return;
        }

        // 2. 處理全店統計資料 (天氣、店數) - 這些通常在標頭列之上
        $this->processGlobalStats($rows, $headerRowIndex);

        // 3. 開始遍歷資料列
        for ($i = $headerRowIndex + 1; $i < $rows->count(); $i++) {
            $row = $rows[$i];

            // 讀取代號 (Column B / Index 1) 與 名稱 (Column C / Index 2)
            $codeInRow = trim($row[1] ?? '');
            $nameInRow = trim($row[2] ?? '');

            // 處理合併儲存格：若有新代號則更新 current，否則沿用
            if (!empty($codeInRow)) {
                $this->currentCode = $codeInRow;
                $this->currentName = $nameInRow;

                // **關鍵邏輯：如果是兩碼或三碼，這裡要負責更新 Categories 表**
                $this->updateCategoryMaster($this->currentCode, $this->currentName);
            }

            // 若無有效代號，跳過
            if (empty($this->currentCode)) {
                continue;
            }

            // 讀取項目名稱 (Column D / Index 3) -> 例如 "實銷金額_合計"
            $metricName = trim($row[3] ?? '');
            if (empty($metricName)) {
                continue;
            }

            // 判斷對應哪個 DB 欄位
            $dbField = $this->mapMetricToDbField($metricName);
            if (!$dbField) {
                continue;
            }

            // 將各月份數值寫入對應的 Summary 表
            $this->saveSummaryData($this->currentCode, $dbField, $row);
        }
    }

    /**
     * 判斷並更新 Categories 主檔 (僅針對 2碼 與 3碼)
     */
    private function updateCategoryMaster($code, $name)
    {
        $len = strlen($code);

        // 只有 2 碼或 3 碼才視為 Category
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

    /**
     * 將資料寫入對應的 Monthly Summary 表
     */
    private function saveSummaryData($code, $dbField, $row)
    {
        $len = strlen($code);

        // 遍歷所有日期欄位
        foreach ($this->dateColumnMap as $colIndex => $dateData) {
            $year = $dateData['year'];
            $month = $dateData['month'];
            $value = $this->parseNumber($row[$colIndex] ?? 0);

            // 根據代號長度決定寫入哪張表
            if ($len === 2) {
                // 兩碼
                Category2digitMonthlySummary::updateOrCreate(
                    ['year' => $year, 'month' => $month, 'category_code' => $code],
                    [$dbField => $value]
                );
            } elseif ($len === 3) {
                // 三碼
                Category3digitMonthlySummary::updateOrCreate(
                    ['year' => $year, 'month' => $month, 'category_code' => $code],
                    [$dbField => $value]
                );
            } elseif ($len === 7) {
                // 七碼 (單品)
                ProductMonthlySummary::updateOrCreate(
                    ['year' => $year, 'month' => $month, 'product_code' => $code],
                    [$dbField => $value]
                );
            }
        }
    }

    /**
     * 處理全店統計 (MonthlyStoreStats)
     * 搜尋範圍：標頭列之前
     */
    private function processGlobalStats($rows, $headerRowIndex)
    {
        for ($i = 0; $i < $headerRowIndex; $i++) {
            $row = $rows[$i];

            // 將整列轉為字串以方便搜尋關鍵字
            $rowStr = implode(' ', $row->toArray());

            $metricField = null;
            if (str_contains($rowStr, '既存店數')) {
                $metricField = 'existing_store_count';
            } elseif (str_contains($rowStr, '去年同期天氣')) {
                $metricField = 'weather_ly';
            } elseif (str_contains($rowStr, '天氣')) {
                // 需排除 "去年同期天氣"
                $metricField = 'weather';
            }

            if ($metricField) {
                foreach ($this->dateColumnMap as $colIndex => $dateData) {
                    $val = $row[$colIndex] ?? null;
                    if (is_null($val)) continue;

                    // 天氣是文字，店數是數字
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

    /**
     * 解析標頭列中的日期 (Format: 113/05 -> Year: 2024, Month: 5)
     */
    private function parseDateHeaders($row)
    {
        foreach ($row as $index => $cell) {
            $cellStr = trim((string)$cell);
            // 嚴格匹配民國年/月，排除 "合計"
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

    private function mapMetricToDbField($rawName)
    {
        foreach ($this->fieldMap as $key => $field) {
            if (str_contains($rawName, $key)) {
                return $field;
            }
        }
        return null;
    }

    private function parseNumber($value)
    {
        if (is_null($value) || $value === '') return 0;
        $cleanValue = str_replace(['%', ','], '', (string)$value);
        return is_numeric($cleanValue) ? floatval($cleanValue) : 0;
    }
}
