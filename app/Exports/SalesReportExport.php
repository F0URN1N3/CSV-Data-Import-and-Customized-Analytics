<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class SalesReportExport implements FromView, ShouldAutoSize, WithTitle
{
    protected $data;
    protected $reportType;
    protected $dateRange;

    public function __construct($data, $reportType, $dateRange)
    {
        $this->data = $data;
        $this->reportType = $reportType;
        $this->dateRange = $dateRange;
    }

    public function view(): View
    {
        // 直接使用 preview 的 blade，但我們可以在 blade 裡針對 excel 做一點小調整
        return view('analysis.report_preview', [
            'data' => $this->data,
            'reportType' => $this->reportType,
            'dateRange' => $this->dateRange,
            'isExcel' => true // 傳入變數，讓 View 知道現在是輸出 Excel，可以隱藏按鈕
        ]);
    }

    public function title(): string
    {
        return '銷售分析報表';
    }
}
