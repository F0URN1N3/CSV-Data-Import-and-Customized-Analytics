<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="utf-8">
    <title>報表預覽 - {{ $reportType }}</title>
    @if(!isset($isExcel))
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    @endif
    <style>
        body { padding: 20px; background: #eee; font-family: "Microsoft JhengHei", sans-serif; }
        .sheet { background: white; padding: 15px; box-shadow: 0 0 10px rgba(0,0,0,0.1); overflow-x: auto; }

        /* 表格基礎 */
        table { border-collapse: collapse; width: 100%; font-size: 13px; white-space: nowrap; }
        th, td { border: 1px solid #999; padding: 6px 8px; text-align: center; vertical-align: middle; }
        .th-product-info{background-color: yellow;}

        /* 顏色與排版 */
        .header-top-left { background-color: #f8f9fa; font-weight: bold; font-size: 1.1em; }
        .header-year { background-color: #d1e7dd; font-weight: bold; font-size: 1.2em; }
        .header-year-ly { background-color: #fff3cd; font-weight: bold; font-size: 1.2em; }
        .header-diff { background-color: #f8d7da; font-weight: bold; font-size: 1.2em; }
        .header-month { background-color: #f0f0f0; font-weight: bold; }
        .header-detail { background-color: #e2e3e5; font-weight: bold; }

        .row-meta { background-color: #f8f9fa; color: #666; font-weight: bold; }
        .row-2digit {font-weight: bold; }

        .col-code { width: 60px; }
        .col-name { width: 150px; text-align: left; }
        .val-cell { width: 80px; text-align: right; font-family: Consolas, monospace; }
        .text-right { text-align: right; }

        @media print {
            .no-print { display: none; }
            body { padding: 0; background: white; }
            .sheet { box-shadow: none; padding: 0;}
        }
    </style>
</head>
<body>

    @php
        // 定義 Excel 專用的背景顏色
        $colorHeaderYear = isset($isExcel) ? 'background-color: #d1e7dd;' : '';
        $colorHeaderYearLY = isset($isExcel) ? 'background-color: #fff3cd;' : '';
        $colorHeaderDiff = isset($isExcel) ? 'background-color: #f8d7da;' : '';
        $colorProductInfo = isset($isExcel) ? 'background-color: #FFFF00;' : '';
        $colorHeaderMonth = isset($isExcel) ? 'background-color: #f0f0f0;' : '';
        $colorHeaderYear_total = isset($isExcel) ? 'background-color: #6cbc7c;' : '';
        $colorHeaderYearLY_total = isset($isExcel) ? 'background-color: #f0e68c;' : '';
        $colorHeaderDiff_total = isset($isExcel) ? 'background-color: #cd5c5c;' : '';

    @endphp

    <div class="container-fluid">

        {{-- 判斷：如果不是 Excel 匯出，才顯示標題與按鈕區塊 --}}
        @if(!isset($isExcel))

        <div class="d-flex justify-content-between align-items-center mb-3 no-print">
            <h4 class="mb-0 fw-bold">
                @switch($reportType)
                    @case('category-psd') 成績單 - 品群實銷金額 (PSD) @break
                    @case('product-sales-diff') 成績單 - 單品實銷金額差異 @break
                    @case('product-quantity-diff') 成績單 - 單品銷售數量差異 @break
                    @case('product-detail') 成績單 - 單品詳細資料 @break
                @endswitch
                <span class="fs-6 text-muted ms-2">
                    {{ $reportType == 'product-detail' ? '月份: ' : '區間: ' }} {{ $dateRange }}
                </span>
            </h4>
            <div>
                <form action="{{ route('analysis.download') }}" method="POST" style="display:inline;">
                    @csrf
                    {{-- 這裡需要把所有的查詢參數用 hidden input 傳過去 --}}
                    <input type="hidden" name="report_type" value="{{ $reportType }}">
                    <input type="hidden" name="start_date" value="{{ explode(' ~ ', $dateRange)[0] }}">
                    <input type="hidden" name="end_date" value="{{ count(explode(' ~ ', $dateRange)) > 1 ? explode(' ~ ', $dateRange)[1] : explode(' ~ ', $dateRange)[0] }}">
                    @if(isset($data['rows']))
                        @foreach($data['rows'] as $code => $row)
                            <input type="hidden" name="product_codes[]" value="{{ $code }}">
                        @endforeach
                    @elseif($reportType == 'product-detail')
                        @foreach($data as $row)
                            <input type="hidden" name="product_codes[]" value="{{ $row['product_code'] }}">
                        @endforeach
                    @endif
                    <button type="submit" class="btn btn-primary"><i class="bi bi-file-earmark-excel"></i> 下載 Excel</button>
                </form>
                <button class="btn btn-danger" onclick="window.close()">關閉</button>
            </div>
        </div>

        @endif

        <div class="sheet">
            {{-- 檢查有無資料 --}}
            @if(empty($data) || (is_array($data) && empty($data['rows']) && $reportType != 'product-detail') || ($reportType == 'product-detail' && count($data) == 0))
                <div class="alert alert-warning text-center">此區間查無資料</div>
            @else

                {{-- ======================================================= --}}
                {{-- 1. 品群 PSD (維持原樣) --}}
                {{-- ======================================================= --}}
                @if($reportType == 'category-psd')
                    <table class="table-bordered">
                        <thead>
                            <tr>
                                <th colspan="2" rowspan="2" class="header-top-left">實銷金額PSD</th>
                                @php
                                    $pStart = $data['periods'][0];
                                    $pEnd   = end($data['periods']);
                                    $yStart = substr($pStart, 0, 4);
                                    $yEnd   = substr($pEnd, 0, 4);
                                    $title  = ($yStart == $yEnd) ? "{$yStart} 年" : "{$yStart} - {$yEnd} 年";

                                    $pStartLY = $data['periods_ly'][0];
                                    $pEndLY   = end($data['periods_ly']);
                                    $yStartLY = substr($pStartLY, 0, 4);
                                    $yEndLY   = substr($pEndLY, 0, 4);
                                    $titleLY  = ($yStartLY == $yEndLY) ? "{$yStartLY} 年" : "{$yStartLY} - {$yEndLY} 年";
                                @endphp
                                <th colspan="{{ count($data['periods']) + 1 }}" class="header-year" style="{{$colorHeaderYear}}">{{ $title }} (本期)</th>
                                <th colspan="{{ count($data['periods_ly']) + 1 }}" class="header-year-ly" style="{{$colorHeaderYearLY}}">{{ $titleLY }} (去年同期)</th>
                            </tr>
                            <tr>
                                @foreach($data['periods'] as $p) <th class="header-month" style="{{$colorHeaderMonth}}">{{ substr($p, 5, 2) }}月</th> @endforeach
                                <th class="header-month">合計</th>
                                @foreach($data['periods_ly'] as $p) <th class="header-month" style="{{$colorHeaderMonth}}">{{ substr($p, 5, 2) }}月</th> @endforeach
                                <th class="header-month">合計</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="row-meta">
                                <td></td> <td class="text-start ps-3">天數</td>
                                @foreach($data['periods'] as $p) <td>{{ $data['metadata'][$p]['days'] ?? '-' }}</td> @endforeach
                                <td></td>
                                @foreach($data['periods_ly'] as $p) <td>{{ $data['metadata'][$p]['days'] ?? '-' }}</td> @endforeach
                                <td></td>
                            </tr>
                            <tr class="row-meta">
                                <td></td> <td class="text-start ps-3">既存店數</td>
                                @foreach($data['periods'] as $p) <td>{{ number_format($data['metadata'][$p]['store_count'] ?? 0) }}</td> @endforeach
                                <td></td>
                                @foreach($data['periods_ly'] as $p) <td>{{ number_format($data['metadata'][$p]['store_count'] ?? 0) }}</td> @endforeach
                                <td></td>
                            </tr>
                            @foreach($data['rows'] as $code => $row)
                                <tr class="{{ $row['is_2digit'] ? 'row-2digit' : '' }}">
                                    <td class="col-code">{{ $code }}</td>
                                    <td class="col-name">{{ $row['name'] }}</td>
                                    @foreach($data['periods'] as $p)
                                        <td class="val-cell">{{ isset($row['data'][$p]) ? number_format($row['data'][$p], 2) : '' }}</td>
                                    @endforeach
                                    <td class="val-cell fw-bold bg-success-subtle">{{ number_format($row['total_current'], 2) }}</td>
                                    @foreach($data['periods_ly'] as $p)
                                        <td class="val-cell text-muted">{{ isset($row['data_ly'][$p]) ? number_format($row['data_ly'][$p], 2) : '' }}</td>
                                    @endforeach
                                    <td class="val-cell fw-bold bg-warning-subtle">{{ number_format($row['total_ly'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                {{-- ======================================================= --}}
                {{-- 2 & 3. 單品差異分析 (矩陣) --}}
                {{-- ======================================================= --}}
                @elseif($reportType == 'product-sales-diff' || $reportType == 'product-quantity-diff')
                    <table class="table-bordered">
                        <thead>
                            <tr>
                                <th colspan="11" class="header-top-left">商品資訊</th>
                                @php
                                    $pStart = $data['periods'][0];
                                    $yStart = substr($pStart, 0, 4);
                                    $yLY    = $yStart - 1;
                                @endphp
                                <th colspan="{{ count($data['periods']) + 1 }}" class="header-year" style="{{$colorHeaderYear}}">{{ $yStart }} 年 (本期)</th>
                                <th colspan="{{ count($data['periods']) + 1 }}" class="header-year-ly" style="{{$colorHeaderYearLY}}">{{ $yLY }} 年 (去年同期)</th>
                                <th colspan="{{ count($data['periods']) + 1 }}" class="header-diff" style="{{$colorHeaderDiff}}">前期差異</th>
                            </tr>
                            <tr>
                                {{-- 商品基本資料 --}}
                                <th class="th-product-info" style="{{$colorProductInfo}}">代號</th>
                                <th class="th-product-info" style="{{$colorProductInfo}}">品牌</th>
                                <th class="th-product-info" style="{{$colorProductInfo}}">商品名稱</th>
                                <th class="th-product-info" style="{{$colorProductInfo}}">規格</th>
                                <th class="th-product-info" style="{{$colorProductInfo}}">廠價</th>
                                <th class="th-product-info" style="{{$colorProductInfo}}">店價</th>
                                <th class="th-product-info" style="{{$colorProductInfo}}">售價</th>
                                <th class="th-product-info" style="{{$colorProductInfo}}">毛利率%</th>
                                <th class="th-product-info" style="{{$colorProductInfo}}">保存期限</th>
                                <th class="th-product-info" style="{{$colorProductInfo}}">品號</th>
                                <th class="th-product-info" style="{{$colorProductInfo}}">群號</th>
                                @foreach($data['periods'] as $p) <th class="header-month" style="{{$colorHeaderMonth}}">{{ substr($p, 5, 2) }}月</th> @endforeach
                                <th class="header-month bg-success text-white" style="{{$colorHeaderYear_total}}">合計</th>
                                @foreach($data['periods'] as $p) <th class="header-month" style="{{$colorHeaderMonth}}">{{ substr($p, 5, 2) }}月</th> @endforeach
                                <th class="header-month bg-warning text-white" style="{{$colorHeaderYearLY_total}}">合計</th>
                                @foreach($data['periods'] as $p) <th class="header-month" style="{{$colorHeaderMonth}}">{{ substr($p, 5, 2) }}月</th> @endforeach
                                <th class="header-month bg-danger text-white" style="{{$colorHeaderDiff_total}}">合計</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['rows'] as $row)
                                <tr>
                                    {{-- 商品基本資料 --}}
                                    <td class="col-code">{{ $row['product_code'] }}</td>
                                    <td>{{ $row['brand'] }}</td>
                                    <td class="col-name">{{ $row['name'] }}</td>
                                    <td>{{ $row['spec'] }}</td>
                                    <td class="text-right">{{ number_format($row['factory_price'] ?? 0, 2) }}</td>
                                    <td class="text-right">{{ number_format($row['store_price'] ?? 0, 2) }}</td>
                                    <td class="text-right fw-bold">{{ number_format($row['selling_price'] ?? 0, 2) }}</td>
                                    {{-- gross_margin_pct (DB 儲存 0.12345，顯示時乘以 100) --}}
                                    <td class="text-right">{{ number_format($row['gross_margin_pct'] * 100 ?? 0, 2) }}%</td>
                                    <td>{{ $row['shelf_life'] }}</td>
                                    <td>{{ $row['category_code_1'] }}</td>
                                    <td>{{ $row['category_code_2'] }}</td>


                                    {{-- 本期 --}}
                                    @foreach($data['periods'] as $p)
                                        <td class="val-cell">{{ number_format($row['curr'][$p]) }}</td>
                                    @endforeach
                                    <td class="val-cell fw-bold bg-success-subtle">{{ number_format($row['total_curr']) }}</td>

                                    {{-- 去年 --}}
                                    @foreach($data['periods'] as $p)
                                        <td class="val-cell text-muted">{{ number_format($row['ly'][$p]) }}</td>
                                    @endforeach
                                    <td class="val-cell fw-bold bg-warning-subtle">{{ number_format($row['total_ly']) }}</td>

                                    {{-- 差異 --}}
                                    @foreach($data['periods'] as $p)
                                        @php $val = $row['diff'][$p]; @endphp
                                        <td class="val-cell {{ $val < 0 ? 'text-danger' : 'text-success' }}">{{ number_format($val) }}</td>
                                    @endforeach
                                    <td class="val-cell fw-bold bg-danger-subtle">{{ number_format($row['total_diff']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                {{-- ======================================================= --}}
                {{-- 4. 單品詳細資料 (單月清單) --}}
                {{-- ======================================================= --}}
                @elseif($reportType == 'product-detail')
                    <table class="table-bordered">
                        <thead>
                            <tr class="header-detail">
                                {{-- 商品基本資料 --}}
                                <th class="th-product-info" style="{{$colorProductInfo}}">代號</th>
                                <th class="th-product-info" style="{{$colorProductInfo}}">品牌</th>
                                <th class="th-product-info" style="{{$colorProductInfo}}">商品名稱</th>
                                <th class="th-product-info" style="{{$colorProductInfo}}">規格</th>
                                <th class="th-product-info" style="{{$colorProductInfo}}">廠價</th>
                                <th class="th-product-info" style="{{$colorProductInfo}}">店價</th>
                                <th class="th-product-info" style="{{$colorProductInfo}}">售價</th>
                                <th class="th-product-info" style="{{$colorProductInfo}}">毛利率%</th>
                                <th class="th-product-info" style="{{$colorProductInfo}}">保存期限</th>
                                <th class="th-product-info" style="{{$colorProductInfo}}">品號</th>
                                <th class="th-product-info" style="{{$colorProductInfo}}">群號</th>

                                {{-- 銷售指標 --}}
                                <th style="{{$colorHeaderMonth}}">導入店數</th>
                                <th style="{{$colorHeaderMonth}}">進貨店數</th>
                                <th style="{{$colorHeaderMonth}}">銷售店數</th>
                                <th style="{{$colorHeaderMonth}}">導入店率%</th>
                                <th style="{{$colorHeaderMonth}}">進貨店率</th>

                                {{-- 實銷金額 --}}
                                <th style="{{$colorHeaderMonth}}">實銷金額</th>
                                <th style="{{$colorHeaderMonth}}">實銷金額_前年實績</th>
                                <th style="{{$colorHeaderMonth}}">實銷金額_前年差</th>
                                <th style="{{$colorHeaderMonth}}">實銷金額_前年比%</th>
                                <th style="{{$colorHeaderMonth}}">實銷金額_構成比%</th>

                                {{-- 銷售數量 --}}
                                <th style="{{$colorHeaderMonth}}">進貨數量</th>
                                <th style="{{$colorHeaderMonth}}">進貨數量_前年實績</th>
                                <th style="{{$colorHeaderMonth}}">銷售數量</th>
                                <th style="{{$colorHeaderMonth}}">銷售數量_前年差</th>
                                <th style="{{$colorHeaderMonth}}">銷售數量_前年比%</th>
                                <th style="{{$colorHeaderMonth}}">廢棄數量</th>
                                <th style="{{$colorHeaderMonth}}">廢棄數量_前年實績</th>
                                <th style="{{$colorHeaderMonth}}">退貨數量</th>
                                <th style="{{$colorHeaderMonth}}">退貨數量_前年實績</th>
                                <th style="{{$colorHeaderMonth}}">轉貨數量</th>
                                <th style="{{$colorHeaderMonth}}">轉貨數量_前年實績</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data as $row)
                                <tr>
                                    {{-- 商品基本資料 --}}
                                    <td class="col-code">{{ $row['product_code'] }}</td>
                                    <td>{{ $row['brand'] }}</td>
                                    <td class="col-name">{{ $row['name'] }}</td>
                                    <td>{{ $row['spec'] }}</td>
                                    <td class="text-right">{{ number_format($row['factory_price'] ?? 0, 2) }}</td>
                                    <td class="text-right">{{ number_format($row['store_price'] ?? 0, 2) }}</td>
                                    <td class="text-right fw-bold">{{ number_format($row['selling_price'] ?? 0, 2) }}</td>
                                    {{-- gross_margin_pct (DB 儲存 0.12345，顯示時乘以 100) --}}
                                    <td class="text-right">{{ number_format($row['gross_margin_pct'] * 100 ?? 0, 2) }}%</td>
                                    <td>{{ $row['shelf_life'] }}</td>
                                    <td>{{ $row['category_code_1'] }}</td>
                                    <td>{{ $row['category_code_2'] }}</td>

                                    {{-- 銷售指標 --}}
                                    <td class="text-right">{{ number_format($row['active_store_count'] ?? 0) }}</td>
                                    <td class="text-right">{{ number_format($row['stock_in_store_count'] ?? 0) }}</td>
                                    <td class="text-right">{{ number_format($row['sales_store_count'] ?? 0) }}</td>
                                    <td class="text-right">{{ number_format($row['active_store_rate_pct'] ?? 0, 2) }}%</td>
                                    <td class="text-right">{{ number_format($row['stock_in_store_rate_pct'] ?? 0, 2) }}%</td>

                                    {{-- 實銷金額 --}}
                                    <td class="text-right text-primary fw-bold">{{ number_format($row['sales_amount'] ?? 0) }}</td>
                                    <td class="text-right text-muted">{{ number_format($row['sales_amount_ly'] ?? 0) }}</td>
                                    <td class="text-right {{ ($row['sales_amount_diff'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($row['sales_amount_diff'] ?? 0) }}
                                    </td>
                                    <td class="text-right">{{ number_format($row['sales_amount_yoy_pct'] ?? 0, 2) }}%</td>
                                    <td class="text-right">{{ number_format($row['sales_amount_mix_pct'] ?? 0, 2) }}%</td>

                                    {{-- 銷售數量 (進貨) --}}
                                    <td class="text-right">{{ number_format($row['stock_in_quantity'] ?? 0) }}</td>
                                    <td class="text-right text-muted">{{ number_format($row['stock_in_quantity_ly'] ?? 0) }}</td>

                                    {{-- 銷售數量 (實銷) --}}
                                    <td class="text-right text-primary fw-bold">{{ number_format($row['sales_quantity'] ?? 0) }}</td>
                                    <td class="text-right {{ ($row['sales_quantity_diff'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($row['sales_quantity_diff'] ?? 0) }}
                                    </td>
                                    <td class="text-right">{{ number_format($row['sales_quantity_yoy_pct'] ?? 0, 2) }}%</td>

                                    {{-- 其他數量 --}}
                                    <td class="text-right">{{ number_format($row['waste_quantity'] ?? 0) }}</td>
                                    <td class="text-right text-muted">{{ number_format($row['waste_quantity_ly'] ?? 0) }}</td>
                                    <td class="text-right">{{ number_format($row['return_quantity'] ?? 0) }}</td>
                                    <td class="text-right text-muted">{{ number_format($row['return_quantity_ly'] ?? 0) }}</td>
                                    <td class="text-right">{{ number_format($row['transfer_quantity'] ?? 0) }}</td>
                                    <td class="text-right text-muted">{{ number_format($row['transfer_quantity_ly'] ?? 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            @endif
        </div>
    </div>
</body>
</html>
