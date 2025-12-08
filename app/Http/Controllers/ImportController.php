<?php

namespace App\Http\Controllers;

use App\Services\ExcelImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ImportController extends Controller
{
    protected $importService;

    // 建構子注入：Laravel 會自動幫你把 ExcelImportService new 出來塞進去
    public function __construct(ExcelImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * 處理商品主檔匯入
     */
    public function uploadProductMaster(Request $request)
    {
        // 1. 驗證
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240', // 10MB
        ]);

        try {
            // 2. 呼叫 Service
            $this->importService->importProducts($request->file('file'));

            // 3. 回傳成功
            return response()->json([
                'success' => true,
                'message' => '商品主檔匯入成功'
            ]);

        } catch (\Exception $e) {
            // 4. 回傳失敗
            return response()->json([
                'success' => false,
                'message' => '匯入失敗：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 處理統計報表匯入
     */
    public function uploadMonthlyStats(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:20480', // 20MB
        ]);

        try {
            $this->importService->importMonthlyStats($request->file('file'));

            return response()->json([
                'success' => true,
                'message' => '月度統計匯入成功'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '匯入失敗：' . $e->getMessage()
            ], 500);
        }
    }
}
