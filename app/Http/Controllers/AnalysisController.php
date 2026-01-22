<?php

namespace App\Http\Controllers;

use App\Models\MonthlyStoreStat;
use App\Services\SalesAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\SalesReportExport;
use Maatwebsite\Excel\Facades\Excel;

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
    public function query(Request $request)
    {
        $reportType = $request->query('report', 'category-psd');
        return view('analysis.query', compact('reportType'));
        //compact('reportType') 等同於 ['reportType' => $reportType]，將 $reportType 變數傳遞給視圖，讓視圖可以使用 $reportType 這個變數。
    }

    /**
     * API: 取得資料庫中現有的年份與月份範圍
     * 用於前端初始化時間選擇器
     */
    public function getAvailableDates()
    {
        // 假設 monthly_store_stats (全店統計) 是最完整的時間軸
        // 撈出所有不重複的 年/月 組合，並按時間倒序排列
        $dates = MonthlyStoreStat::select('year', 'month')
            ->distinct()
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->groupBy('year') // 步驟一：將 Collection 以 'year' 欄位分組
            // 結果：{ "2025": [item1, item2, ...], "2024": [...] }，其中 item 是原始的物件

            ->map(function ($items) { // 步驟二：處理分組後的每個子 Collection
            return $items->pluck('month')->values();
            // $items->pluck('month')：從每個子 Collection 中取出所有的 'month' 值
            // ->values()：重設陣列索引，確保最終輸出的是 [12, 11, ...] 而非 [0 => 12, 1 => 11, ...]
            });

    return response()->json($dates);

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
        $products = $request->input('product_codes', []);

        $data = []; // 初始化為陣列
        $viewName = 'analysis.report_preview';

        // 防呆
        if (in_array($type, ['product-sales-diff', 'product-quantity-diff', 'product-detail']) && empty($products)) {
            return back()->withErrors('請至少選擇一項商品');
        }

        switch ($type) {
            case 'category-psd':
                $data = $this->analysisService->analyzeCategoryPsdMatrix($start, $end);
                break;

            case 'product-sales-diff':
                $data = $this->analysisService->analyzeProductVariantMatrix($start, $end, $products, 'sales_amount');
                break;

            case 'product-quantity-diff':
                $data = $this->analysisService->analyzeProductVariantMatrix($start, $end, $products, 'sales_quantity');
                break;

            case 'product-detail':
                // 單品詳細：只取 start_date (因為單一月份)
                $data = $this->analysisService->analyzeProductDetail($start, $products);
                break;
        }

        return view($viewName, [
            'reportType' => $type,
            'data' => $data, // 這裡傳入的是矩陣結構 Array
            'dateRange' => ($type == 'product-detail') ? $start : "$start ~ $end"
        ]);
    }

    public function download(Request $request)
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
        $products = $request->input('product_codes', []);

        // 呼叫 Service 取得資料 (邏輯與 preview 完全相同)
        switch ($type) {
            case 'category-psd':
                $data = $this->analysisService->analyzeCategoryPsdMatrix($start, $end);
                break;
            case 'product-sales-diff':
                $data = $this->analysisService->analyzeProductVariantMatrix($start, $end, $products, 'sales_amount');
                break;
            case 'product-quantity-diff':
                $data = $this->analysisService->analyzeProductVariantMatrix($start, $end, $products, 'sales_quantity');
                break;
            case 'product-detail':
                $data = $this->analysisService->analyzeProductDetail($start, $products);
                break;
        }

        $fileName = "Report_" . $type . "_" . now()->format('YmdHis') . ".xlsx";

        return Excel::download(
            new SalesReportExport($data, $type, "$start ~ $end"),
            $fileName
        );
    }
}
