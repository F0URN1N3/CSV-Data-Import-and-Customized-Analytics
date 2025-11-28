<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImportController;

// 資料匯入群組
Route::prefix('import')->group(function () {
    // 上傳商品主檔 (對應 products table)
    Route::post('/products', [ImportController::class, 'uploadProductMaster'])->name('import.products');

    // 上傳月度統計報表 (對應 monthly_stats table)
    Route::post('/stats', [ImportController::class, 'uploadMonthlyStats'])->name('import.stats');
});
