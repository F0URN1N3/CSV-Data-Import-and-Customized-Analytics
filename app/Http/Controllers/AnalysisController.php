<?php

namespace App\Http\Controllers;

use App\Models\MonthlyStoreStat;
use App\Services\SalesAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalysisController extends Controller
{
    protected $analysisService;
    // 注入 Service
    public function __construct(SalesAnalysisService $analysisService)
    {
        $this->analysisService = $analysisService;
    }

    /**
     * 顯示分析報表主頁面
     */
    public function index()
    {
        return view('analysis.index');
    }

    /**
     * 顯示查詢工作站
     */
    public function query()
    {
        // 回傳高密度查詢頁
        return view('analysis.query');
    }

    /**
     * API: 取得資料庫中現有的年份與月份範圍
     * 用於前端初始化時間選擇器
     */
    public function getAvailableDates()
    {
        // 我們假設 monthly_store_stats (全店統計) 是最完整的時間軸
        // 撈出所有不重複的 年/月 組合，並按時間倒序排列
        $dates = MonthlyStoreStat::select('year', 'month')
            ->distinct()
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        // 整理成前端好用的結構
        // 格式: { "2025": [10, 9, ...], "2024": [12, 11, ...] }
        $result = [];
        foreach ($dates as $date) {
            $result[$date->year][] = $date->month;
        }

        return response()->json($result);
    }

    /**
     *  接收表單，產出預覽報表 (HTML)
     */
    public function preview(Request $request)
    {
        // 1. 驗證資料
        $validated = $request->validate([
            'report_type' => 'required|string',
            'start_date'  => 'required|date_format:Y-m', // 從 Hidden Input 來
            'end_date'    => 'required|date_format:Y-m',
            'product_codes' => 'nullable|array', // 部分報表需要
        ]);

        $type = $validated['report_type'];
        $start = $validated['start_date'];
        $end = $validated['end_date'];

        $data = collect([]);
        $viewName = 'analysis.report_preview'; // 統一用一個 View，內部再 switch

        // 2. 根據報表類型呼叫 Service
        switch ($type) {
            case 'category-psd':
                $data = $this->analysisService->analyzeCategoryPsd($start, $end);
                break;

            // 其他報表類型之後再補...
            case 'product-sales-diff':
            case 'product-quantity-diff':
            case 'product-detail':
                return "此報表功能開發中...";
        }

        // 3. 回傳預覽 View
        return view($viewName, [
            'reportType' => $type,
            'data' => $data,
            'dateRange' => "$start ~ $end"
        ]);
    }
}
