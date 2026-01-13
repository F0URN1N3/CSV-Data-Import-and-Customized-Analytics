<?php

namespace App\Services;

use App\Models\Category2digitMonthlySummary;
use App\Models\Category3digitMonthlySummary;
use App\Models\MonthlyStoreStat;
use App\Models\ProductMonthlySummary;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SalesAnalysisService
{
    /**
     * 成績單 - 品群實銷金額 (PSD) - 矩陣版
     * 回傳結構：
     * [
     * 'periods' => ['2025-01', '2025-02'...], // 本期月份列
     * 'periods_ly' => ['2024-01', '2024-02'...], // 去年同期月份列
     * 'metadata' => [
     * '2025-01' => ['days' => 31, 'store_count' => 1500],
     * ...
     * ],
     * 'rows' => [
     * '311' => [
     * 'name' => '紅茶',
     * 'is_2digit' => false,
     * 'data' => ['2025-01' => 12.5, '2025-02' => 13.0 ...], // 本期 PSD
     * 'data_ly' => ['2024-01' => 11.0 ...], // 去年 PSD
     * 'total' => 150.5, // 本期合計
     * ]
     * ]
     * ]
     */
    public function analyzeCategoryPsdMatrix(string $startMonth, string $endMonth): array
    {
        // 1. 定義時間軸
        $start = Carbon::parse($startMonth . '-01');
        $end   = Carbon::parse($endMonth . '-01')->endOfMonth();

        $startLY = $start->copy()->subYear();
        $endLY   = $end->copy()->subYear();

        // [關鍵修正 1] 轉換為整數格式 YYYYMM (例如 202405)，解決跨年份查詢問題
        $startInt = $start->year * 100 + $start->month;
        $endInt   = $end->year * 100 + $end->month;

        $startLYInt = $startLY->year * 100 + $startLY->month;
        $endLYInt   = $endLY->year * 100 + $endLY->month;

        // 產生月份清單 (用於表頭迴圈)
        $periods = [];
        $periodsLY = [];
        $curr = $start->copy();
        while ($curr <= $end) {
            $periods[] = $curr->format('Y-m');
            $periodsLY[] = $curr->copy()->subYear()->format('Y-m');
            $curr->addMonth();
        }

        // 2. 準備全店統計資料 (使用整數區間撈取)
        // 為了保險，我們一次撈取「去年開始 ~ 今年結束」的大範圍，避免漏掉
        $storeStats = $this->getStoreStats($startLYInt, $endInt);

        $metadata = [];
        $allPeriods = array_merge($periods, $periodsLY);

        foreach ($allPeriods as $p) {
            $y = (int)substr($p, 0, 4);
            $m = (int)substr($p, 5, 2);
            $days = Carbon::createFromDate($y, $m, 1)->daysInMonth;

            // 確保 Key 格式為 YYYY-MM
            $key = sprintf('%04d-%02d', $y, $m);

            // [關鍵修正 2] 改讀取 existing_store_count (既存店數)，原本寫 active_store_count 是錯的
            $storeCount = $storeStats[$key]->existing_store_count ?? 0;

            $metadata[$p] = [
                'days' => $days,
                'store_count' => $storeCount
            ];
        }

        // 3. 撈取 2碼 & 3碼 資料 (使用整數區間)
        $data3 = $this->get3DigitData($startInt, $endInt);
        $data3LY = $this->get3DigitData($startLYInt, $endLYInt);

        $data2 = $this->get2DigitData($startInt, $endInt);
        $data2LY = $this->get2DigitData($startLYInt, $endLYInt);

        // 4. 組裝資料列
        $rows = [];

        $processRow = function ($collection, $isLY) use (&$rows, $metadata) {
            $totalField = $isLY ? 'total_ly' : 'total_current'; // 總計欄位

            foreach ($collection as $item) {
                $code = $item->category_code;
                $ym = sprintf('%04d-%02d', $item->year, $item->month);

                // 初始化 row
                if (!isset($rows[$code])) {
                    $rows[$code] = [
                        'code' => $code,
                        'name' => $item->category_name,
                        'is_2digit' => strlen($code) === 2,
                        'data' => [],
                        'data_ly' => [],
                        'total_current' => 0.0, // 本期合計
                        'total_ly' => 0.0,      // 去年合計
                    ];
                }

                $val = strlen($code) === 2 ? $item->sales_amount_total : $item->sales_amount;

                // 取得分母
                $denom = ($metadata[$ym]['store_count'] ?? 0) * ($metadata[$ym]['days'] ?? 0);

                // 計算 PSD
                $psd = ($denom > 0) ? ($val / $denom) : 0;

                if ($isLY) {
                    $rows[$code]['data_ly'][$ym] = $psd;
                    $rows[$code]['total_ly'] += $psd; // 累加 PSD 數值
                } else {
                    $rows[$code]['data'][$ym] = $psd;
                    $rows[$code]['total_current'] += $psd; // 累加 PSD 數值
                }
            }
        };

        $processRow($data3, false);
        $processRow($data3LY, true);
        $processRow($data2, false);
        $processRow($data2LY, true);

        ksort($rows);

        return [
            'periods' => $periods,
            'periods_ly' => $periodsLY,
            'metadata' => $metadata,
            'rows' => $rows
        ];
    }

    /**
     * 2. & 3. 成績單 - 單品差異分析 (金額/數量) - 矩陣版
     */
    public function analyzeProductVariantMatrix(string $startMonth, string $endMonth, array $productCodes, string $metric): array
    {
        $start = Carbon::parse($startMonth . '-01');
        $end   = Carbon::parse($endMonth . '-01')->endOfMonth();
        $startLY = $start->copy()->subYear();

        $startInt = $start->year * 100 + $start->month;
        $endInt   = $end->year * 100 + $end->month;
        $startLYInt = $startLY->year * 100 + $startLY->month;
        $endLYInt   = $end->copy()->subYear()->year * 100 + $end->copy()->subYear()->month;

        // 月份清單
        $periods = [];
        $curr = $start->copy();
        while ($curr <= $end) {
            $periods[] = $curr->format('Y-m');
            $curr->addMonth();
        }

        // 建立 本期月份 => 去年月份 的對應表
        $periodMap = [];
        foreach ($periods as $p) {
            $periodMap[$p] = (int)substr($p, 0, 4) - 1 . substr($p, 4);
        }

        // 撈取資料
        $data = $this->getProductData($startInt, $endInt, $productCodes, $metric);
        $dataLY = $this->getProductData($startLYInt, $endLYInt, $productCodes, $metric);

        $products = Product::whereIn('product_code', $productCodes)
            ->select([
                'product_code',
                'brand',
                'name',
                'spec',
                'shelf_life',
                'factory_price',
                'store_price',
                'selling_price',      // 售價
                'gross_margin_pct',    // 毛利率
                'category_code_1',
                'category_code_2'
            ])
            ->get()
            ->keyBy('product_code');

        $rows = [];
        foreach ($productCodes as $code) {
            $prod = $products[$code] ?? null;
            if (!$prod) continue;

            $row = [
                // --- 商品基本資料 ---
                'product_code'  => $code,
                'brand'         => $prod->brand,
                'name'          => $prod->name,
                'spec'          => $prod->spec,
                'shelf_life'    => $prod->shelf_life,
                'factory_price' => $prod->factory_price,
                'store_price'   => $prod->store_price,
                'selling_price' => $prod->selling_price,
                'gross_margin_pct' => $prod->gross_margin_pct,
                'category_code_1' => $prod->category_code_1,
                'category_code_2' => $prod->category_code_2,

                // --- 矩陣數值資料 ---
                'curr'  => [],
                'ly'    => [],
                'diff'  => [],
                'total_curr' => 0,
                'total_ly'   => 0,
                'total_diff' => 0,
            ];

            foreach ($periods as $p) {
                $pLY = $periodMap[$p];

                $valCurr = $data[$code][$p] ?? 0;
                $valLY   = $dataLY[$code][$pLY] ?? 0;
                $valDiff = $valCurr - $valLY;

                $row['curr'][$p] = $valCurr;
                $row['ly'][$p]   = $valLY;
                $row['diff'][$p] = $valDiff;

                $row['total_curr'] += $valCurr;
                $row['total_ly']   += $valLY;
                $row['total_diff'] += $valDiff;
            }
            $rows[$code] = $row;
        }

        return [
            'periods' => $periods,
            'rows' => $rows
        ];
    }

    /**
     * 4. 成績單 - 單品詳細資料 (單一月份)
     * [修正] 不做任何運算，直接撈取該月份的 ProductMonthlySummary 所有欄位
     */
    public function analyzeProductDetail(string $monthStr, array $productCodes): array
    {
        // $monthStr 格式 "2024-05"
        $year = (int)substr($monthStr, 0, 4);
        $month = (int)substr($monthStr, 5, 2);

        // 直接撈取該月份資料 (Join Product 以取得商品主檔資訊)
        $items = ProductMonthlySummary::query()
            ->join('products', 'product_monthly_summaries.product_code', '=', 'products.product_code')
            ->where('product_monthly_summaries.year', $year)
            ->where('product_monthly_summaries.month', $month)
            ->whereIn('product_monthly_summaries.product_code', $productCodes)
            ->select(
                // 商品基本資料
                'products.*',
                // 統計數據
                'product_monthly_summaries.*'
            )
            ->get();

        // 整理回傳 (為了保持 controller 邏輯一致，這邊稍微轉一下格式，或者直接回傳 collection 也行)
        // 這裡直接回傳 Collection 即可，因為 View loop 起來是一樣的
        return $items->toArray();
    }


    //------------------------------------ 輔助函式 ------------------------------------

    private function getStoreStats($startInt, $endInt)
    {
        return MonthlyStoreStat::query()
            ->whereRaw('(year * 100 + month) >= ?', [$startInt])
            ->whereRaw('(year * 100 + month) <= ?', [$endInt])
            ->get()
            ->keyBy(function ($item) {
                return sprintf('%04d-%02d', $item->year, $item->month);
            });
    }

    /**
     * [修正] 使用整數比對 + Left Join
     */
    private function get3DigitData($startInt, $endInt)
    {
        $tableName = DB::connection()->getTablePrefix() . (new Category3digitMonthlySummary())->getTable();
        return Category3digitMonthlySummary::query()
            ->leftJoin('categories', 'category_3digit_monthly_summaries.category_code', '=', 'categories.category_code')
            ->whereRaw("($tableName.year * 100 + $tableName.month) >= ?", [$startInt])
            ->whereRaw("($tableName.year * 100 + $tableName.month) <= ?", [$endInt])
            ->select(
                'category_3digit_monthly_summaries.year',
                'category_3digit_monthly_summaries.month',
                'category_3digit_monthly_summaries.category_code',
                'categories.name as category_name',
                'category_3digit_monthly_summaries.sales_amount'
            )
            ->get();
    }

    /**
     * [修正] 使用整數比對 + Left Join
     */
    private function get2DigitData($startInt, $endInt)
    {
        $tableName = DB::connection()->getTablePrefix() . (new Category2digitMonthlySummary())->getTable();
        return Category2digitMonthlySummary::query()
            ->leftJoin('categories', 'category_2digit_monthly_summaries.category_code', '=', 'categories.category_code')
            ->whereRaw("($tableName.year * 100 + $tableName.month) >= ?", [$startInt])
            ->whereRaw("($tableName.year * 100 + $tableName.month) <= ?", [$endInt])
            ->select(
                'category_2digit_monthly_summaries.year',
                'category_2digit_monthly_summaries.month',
                'category_2digit_monthly_summaries.category_code',
                'categories.name as category_name',
                'category_2digit_monthly_summaries.sales_amount_total'
            )
            ->get();
    }

    private function getProductData($startInt, $endInt, $codes, $field)
    {
        return ProductMonthlySummary::query()
            ->whereIn('product_code', $codes)
            ->whereRaw('(year * 100 + month) >= ?', [$startInt])
            ->whereRaw('(year * 100 + month) <= ?', [$endInt])
            ->get()
            ->groupBy('product_code')
            ->map(function ($items) use ($field) {
                return $items->mapWithKeys(function ($item) use ($field) {
                    $key = sprintf('%04d-%02d', $item->year, $item->month);
                    return [$key => $item->$field];
                });
            });
    }
}
