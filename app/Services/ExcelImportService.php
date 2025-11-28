<?php

namespace App\Services;

use App\Imports\ProductMasterImport;
use App\Imports\MonthlyStatsImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\UploadedFile;

class ExcelImportService
{
    /**
     * 匯入商品主檔
     */
    public function importProducts(UploadedFile $file)
    {
        // import 接受的第二個參數可以用來指定讀取特定的 Sheet
        // 這裡我們直接實例化 Import 類別，Maatwebsite 會自動處理
        Excel::import(new ProductMasterImport, $file);
    }

    /**
     * 匯入月度統計資料
     */
    public function importMonthlyStats(UploadedFile $file)
    {
        Excel::import(new MonthlyStatsImport, $file);
    }
}
