<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 告訴 Laravel Excel：讀取標題列時，請完全保留原始文字 (None)，不要轉成 Slug。
        // 這樣 "商品代號" 就會是 "商品代號"，不會變成 ""。
        HeadingRowFormatter::default('none');
    }
}
