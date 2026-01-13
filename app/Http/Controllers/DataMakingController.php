<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DataMaking; // 確保路徑正確

class DataMakingController extends Controller{
    /**
     * 顯示輸入表單的視圖
     */
    public function index()
    {
        return view('data-making');
    }

    /**
     * 處理表單提交，生成並顯示報告表格
     */
    public function generateReport(Request $request)
    {
        // 1. 驗證輸入的 Tier (確保輸入是 t1-t5 之一)
        $validated = $request->validate([
            'tier' => 'required|in:t1,t2,t3,t4,t5',
        ]);

        $inputTier = $validated['tier']; // 取得使用者輸入的 Tier

        // 2. 設定 12 個月的 Tier 陣列 (這裡假設所有月份都使用使用者輸入的 Tier)
        $Tiers = array_fill(0, 12, $inputTier);

        $dataMaker = new DataMaking();

        $pivotedData = [
        'month'           => [], // 月份

        'activeStoreCount'     => [],
        'stockInStoreCount'     => [],
        'salesStoreCount'     => [],
        'activeStoreRatePct'     => [],
        'stockInStoreRatePct'     => [],

        'salesAmount'     => [],
        'salesAmountLy'   => [],
        'salesAmountDiff' => [],
        'salesAmountYoy'  => [],
        'salesAmountMix'  => [],

        'stockInQuantity'   => [],
        'stockInQuantityLy' => [],
        'salesQuantity' => [],
        'salesQuantityDiff'  => [],
        'salesQuantityYoyPct'  => [],
        'wasteQuantity'  => [],
        'wasteQuantityLy'  => [],
        'returnQuantity'  => [],
        'returnQuantityLy'  => [],
        'transferQuantity'  => [],
        'transferQuantityLy'  => [],
        ];

        for ($month = 1; $month <= 12; $month++) {
            $tier = $Tiers[$month - 1];

            // 呼叫 storeCount()
            [ $ASC, $SISC, $SSC, $ASRP, $SISRP ] = $dataMaker->storeCountRng();
            // 呼叫 salesAmountRng()
            [ $sA, $sALy, $sADiff, $sAYoy, $sAMix ] = $dataMaker->salesAmountRng($tier);
            // 呼叫 salesQuantityRng()
            [ $SIQ, $SIQLy, $SQ, $SQD, $SQYP, $WQ, $WQLy, $RQ, $RQLy, $TQ, $TQLy] = $dataMaker->QuantityRng($tier);

            // 將每個指標的數據，依序放入對應的陣列中
            $pivotedData['month'][]           = $month;

            $pivotedData['activeStoreCount'][]   = $ASC;
            $pivotedData['stockInStoreCount'][]   = $SISC;
            $pivotedData['salesStoreCount'][]   = $SSC;
            $pivotedData['activeStoreRatePct'][]   = $ASRP;
            $pivotedData['stockInStoreRatePct'][]   = $SISRP;

            $pivotedData['salesAmount'][]     = $sA;
            $pivotedData['salesAmountLy'][]   = $sALy;
            $pivotedData['salesAmountDiff'][] = $sADiff;
            $pivotedData['salesAmountYoy'][]  = $sAYoy;
            $pivotedData['salesAmountMix'][]  = $sAMix;

            $pivotedData['stockInQuantity'][]   = $SIQ;
            $pivotedData['stockInQuantityLy'][]   = $SIQLy;
            $pivotedData['salesQuantity'][]   = $SQ;
            $pivotedData['salesQuantityDiff'][]   = $SQD;
            $pivotedData['salesQuantityYoyPct'][]   = $SQYP;
            $pivotedData['wasteQuantity'][]   = $WQ;
            $pivotedData['wasteQuantityLy'][]   = $WQLy;
            $pivotedData['returnQuantity'][]   = $RQ;
            $pivotedData['returnQuantityLy'][]   = $RQLy;
            $pivotedData['transferQuantity'][]   = $TQ;
            $pivotedData['transferQuantityLy'][]   = $TQLy;
        }



        // 5. 渲染視圖，並傳入數據
        return view('data-making', [
            'reportData' => collect($pivotedData), // 傳入 Collection
            'inputTier' => $inputTier // 傳回使用者輸入的 Tier
        ]);
    }
}
