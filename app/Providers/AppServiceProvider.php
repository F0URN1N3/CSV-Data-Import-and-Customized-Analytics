<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use Illuminate\Support\Facades\URL;

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
        // 檢查是否在生產環境（如 Render），如果是則強制使用 HTTPS
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // 告訴 Laravel Excel：讀取標題列時，請完全保留原始文字 (None)，不要轉成 Slug。
        // 這樣 "商品代號" 就會是 "商品代號"，不會變成 ""。
        HeadingRowFormatter::default('none');
    }
}
