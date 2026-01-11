<?php

namespace App\Imports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Illuminate\Support\Facades\Log;

class ProductMasterImport implements ToModel, WithHeadingRow, WithMultipleSheets
{
    /**
     * WithMultipleSheets需實作
     * 指定只讀取第一個 Sheet (Index 0)
     */
    public function sheets(): array
    {
        return [
            0 => $this,
        ];
    }

    /**
     * 指定 Excel 檔案的第 2 行是標題
     */
    public function headingRow(): int
    {
        return 2;
    }

    /**
     * 對應資料庫欄位
     * 使用 updateOrCreate 確保重複上傳時會更新舊資料
     */
    public function model(array $row)
    {
        // === 【除錯專用】 ===
        // 只印出第一筆資料的 Key，確認程式抓到的標題對不對
        // static $isFirstRow = true;
        // if ($isFirstRow) {
        //     Log::info('目前的 Row 全部的 Keys:', array_keys($row));
        //     Log::info('程式讀到的 Excel 標題 (Keys):', array_keys($row));
        //     $isFirstRow = false;
        // }

        // 確保必要欄位存在，若商品代號為空則跳過
        if (!isset($row['商品代號']) || empty($row['商品代號'])) {
            return null;
        }

        // 處理可能的特殊欄位名稱 (Excel 內有時會有換行)
        // 嘗試抓取 '店舖毛利率' 或 '店舖\n毛利率'
        $grossMargin = $row['店舖毛利率'] ?? $row['店舖_毛利率'] ?? $row["店舖\n毛利率"] ?? null;

        return Product::updateOrCreate(
            ['product_code' => $row['商品代號']], // Primary Key 搜尋條件
            [
                'brand'            => $row['品牌'] ?? null,
                'name'             => $row['商品名稱'] ?? null,
                'spec'             => $row['規格'] ?? null,
                //parseNumber()數值欄位轉型處理，移除可能的逗號
                'factory_price'    => $this->parseNumber($row['廠價'] ?? 0),
                'store_price'      => $this->parseNumber($row['店價'] ?? 0),
                'selling_price'    => $this->parseNumber($row['售價'] ?? 0),
                'gross_margin_pct' => $this->parseNumber($grossMargin ?? 0),
                'shelf_life'       => $row['保存期限'] ?? null,
                'category_code_1'  => $row['品號'] ?? null,
                'category_code_2'  => $row['群號'] ?? null,
            ]
        );
    }

    /**
     * 輔助函數：處理數值字串 (移除千分位逗號)
     */
    private function parseNumber($value)
    {
        if (is_null($value)) return 0;
        return floatval(str_replace(',', '', (string)$value));
    }
}
