<?php

namespace App\Services;

use App\Imports\ProductMasterImport;
use App\Imports\MonthlyStatsImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Exception;

class ExcelImportService
{
    /**
     * 匯入商品主檔
     */
    public function importProducts(UploadedFile $file)
    {
        // 強制設定 PHP 執行時間為無限(0)(或是設定 600 秒)
        set_time_limit(300);

        try{

            Excel::import(new ProductMasterImport, $file);

            Log::info('商品檔'.$file->getClientOriginalName().'匯入成功');

        } catch(Exception $e){

            Log::error('商品檔'.$file->getClientOriginalName().'匯入失敗: ' . $e->getMessage());

            // 把錯誤往上拋，讓 Controller 去決定怎麼回傳給前端
            throw $e;

        }

    }

    /**
     * 匯入月度統計資料
     */
    public function importMonthlyStats(UploadedFile $file)
    {
        try {

            Excel::import(new MonthlyStatsImport, $file);

            Log::info('月度統計'.$file->getClientOriginalName().'匯入成功');

        } catch (\Exception $e) {

            Log::error('月度統計'.$file->getClientOriginalName().'匯入失敗: ' . $e->getMessage());

            throw $e;
        }
    }
}
