<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\AnalysisController;

Route::view('/dashboard', 'dashboard')->name('dashboard');

// 資料匯入群組
Route::prefix('import')->group(function () {
    // 上傳商品主檔 (對應 products table)
    Route::post('/products', [ImportController::class, 'uploadProductMaster'])->name('import.products');

    // 上傳月度統計報表 (對應 monthly_stats table)
    Route::post('/stats', [ImportController::class, 'uploadMonthlyStats'])->name('import.stats');
});

// 分析頁面與相關 API
Route::prefix('analysis')->name('analysis.')->group(function () {
    // 分析主頁
    Route::get('/', [AnalysisController::class, 'index'])->name('index');

    // 查詢工作站頁面 (高密度篩選介面)
    Route::get('/query', [AnalysisController::class, 'query'])->name('query');

    // 取得資料庫現有日期範圍
    Route::get('/dates', [AnalysisController::class, 'getAvailableDates'])->name('dates');

    // 報表預覽 (POST)
    Route::post('/preview', [AnalysisController::class, 'preview'])->name('preview');
});

// 搜尋 API 群組
Route::prefix('search')->name('search.')->group(function () {
    // 搜尋品牌 API: /search/brands?term=古
    Route::get('/brands', [SearchController::class, 'searchBrands'])->name('brands');

    // 搜尋單品 API: /search/products?term=030
    Route::get('/products', [SearchController::class, 'searchProducts'])->name('products');

    // 品牌連動 API: /search/products-by-brands (POST 因為可能傳很多品牌)
    Route::post('/products-by-brands', [SearchController::class, 'getProductsByBrands'])->name('products_by_brands');

    // 三層選單 API
    Route::get('/cats/1', [SearchController::class, 'getCategories1'])->name('cats1');
    Route::get('/cats/2', [SearchController::class, 'getCategories2'])->name('cats2');
    Route::get('/products-by-cat', [SearchController::class, 'getProductsByCategory'])->name('products_by_cat');
});
