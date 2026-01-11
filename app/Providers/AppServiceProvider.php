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
    }
}
