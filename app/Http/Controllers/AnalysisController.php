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
        $validated = $request->validate([
            'report_type' => 'required|string',
            'start_date'  => 'required|date_format:Y-m',
            'end_date'    => 'required|date_format:Y-m',
            'product_codes' => 'nullable|array',
        ]);

        $type = $validated['report_type'];
        $start = $validated['start_date'];
        $end = $validated['end_date'];

        $data = []; // 初始化為陣列
        $viewName = 'analysis.report_preview';

        switch ($type) {
            case 'category-psd':
                // [修正] 改呼叫矩陣版 Service
                $data = $this->analysisService->analyzeCategoryPsdMatrix($start, $end);
                break;

            // ... 其他 case ...
        }

        return view($viewName, [
            'reportType' => $type,
            'data' => $data, // 這裡傳入的是矩陣結構 Array
            'dateRange' => "$start ~ $end"
        ]);
    }
}
