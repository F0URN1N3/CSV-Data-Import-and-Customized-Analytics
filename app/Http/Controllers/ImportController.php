<?php

namespace App\http\Controllers;

use App\Services\ExcelImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ImportController extends Controller
{
    protected $importService;

    // 依賴注入 Service
    public function __construct(ExcelImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * 處理商品主檔匯入
     */
    public function uploadProductMaster(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240', // 限制 10MB
        ]);

        try {
            $this->importService->importProducts($request->file('file'));

            return response()->json([
                'success' => true,
                'message' => '商品資料匯入成功'
            ]);
        } catch (\Exception $e) {
            Log::error('Product Import Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '匯入失敗: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 處理統計報表匯入
     */
    public function uploadMonthlyStats(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:20480',
        ]);

        try {
            $this->importService->importMonthlyStats($request->file('file'));

            return response()->json([
                'success' => true,
                'message' => '統計資料匯入成功'
            ]);
        } catch (\Exception $e) {
            Log::error('Stats Import Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '匯入失敗: ' . $e->getMessage()
            ], 500);
        }
    }
}
