<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Retail Data Analytics - 統計分析工作站</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    @vite(['resources/js/app.js'])

    <style>
        body { background-color: #f0f2f5; height: 100vh; overflow: hidden; /* 讓整個 Body 不捲動 */ }

        /* 左右欄獨立捲動設定 */
        .scrollable-column {
            height: calc(100vh - 60px); /* 扣掉 Navbar 高度 */
            overflow-y: auto;
            padding-bottom: 50px;
        }

        /* 右側表格樣式 */
        .table-sticky-header thead th {
            position: sticky;
            top: 0;
            background-color: #343a40; /* Dark bg */
            color: white;
            z-index: 1;
        }

        /* 讓 Select2 在 Small 模式下也變矮一點 */
        .select2-container .select2-selection--single { height: 31px !important; }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered { line-height: 29px !important; }

        /* 拖曳排序按鈕樣式 */
        .btn-sort { cursor: pointer; padding: 0 5px; color: #6c757d; }
        .btn-sort:hover { color: #000; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm" style="height: 60px;">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <i class="bi bi-graph-up-arrow me-2"></i> Retail Analytics
            </a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('dashboard') }}"><i class="bi bi-cloud-upload me-1"></i> 資料匯入</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active fw-bold" href="#"><i class="bi bi-bar-chart-line me-1"></i> 統計分析</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">

            <div class="col-lg-7 scrollable-column border-end bg-white p-3">

                <h5 class="fw-bold text-secondary mb-3">
                    <i class="bi bi-sliders me-2"></i>
                    @switch(request('report'))
                        @case('category-psd') 成績單 - 品群實銷金額 (PSD) @break
                        @case('product-sales-diff') 成績單 - 單品實銷金額差異 @break
                        @case('product-quantity-diff') 成績單 - 單品銷售數量差異 @break
                        @case('product-detail') 成績單 - 單品詳細資料 @break
                        @default 篩選條件
                    @endswitch
                </h5>

                <form id="analysis-form" method="POST" action="/analysis/preview" target="_blank">
                    @csrf

                    <div class="card mb-3 shadow-sm border-primary">
                        <div class="card-header bg-primary text-white py-1 px-3">
                            <small class="fw-bold">1. 商品選擇</small>
                        </div>
                        <div class="card-body p-2">

                            <div class="row g-2 mb-2">
                                <div class="col-3">
                                    <label class="form-label small fw-bold mb-0">品號</label>
                                    <select id="select-cat1" class="form-select form-select-sm"></select>
                                </div>
                                <div class="col-3">
                                    <label class="form-label small fw-bold mb-0">群號</label>
                                    <select id="select-cat2" class="form-select form-select-sm"></select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold mb-0 text-success">單品 (點擊加入)</label>
                                    <select id="select-product-quick" class="form-select form-select-sm"></select>
                                </div>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-12">
                                    <label class="form-label small fw-bold mb-0">關鍵字搜尋 (代號/名稱)</label>
                                    <select id="select-product-search" class="form-select form-select-sm"></select>
                                </div>
                            </div>

                            <div class="row g-2">
                                <div class="col-5">
                                    <label class="form-label small fw-bold mb-0">品牌篩選</label>
                                    <select id="select-brand" class="form-select form-select-sm"></select>
                                </div>
                                <div class="col-7">
                                    <label class="form-label small fw-bold mb-0 text-primary">品牌商品 (首項為加入全部)</label>
                                    <select id="select-brand-products" class="form-select form-select-sm"></select>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="card mb-3 shadow-sm border-success">
                        <div class="card-header bg-success text-white py-1 px-3">
                            <small class="fw-bold">2. 時間區段</small>
                        </div>
                        <div class="card-body p-2">

                            <div class="row g-2 align-items-center mb-2">
                                <div class="col-3 text-end">
                                    <label class="form-label small fw-bold mb-0">開始時間：</label>
                                </div>
                                <div class="col-9">
                                    <div class="d-flex gap-1">
                                        <select id="date-start-year" class="form-select form-select-sm flex-fill"></select>
                                        <select id="date-start-month" class="form-select form-select-sm flex-fill"></select>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2 align-items-center">
                                <div class="col-3 text-end">
                                    <label class="form-label small fw-bold mb-0">結束時間：</label>
                                </div>
                                <div class="col-9">
                                    <div class="d-flex gap-1">
                                        <select id="date-end-year" class="form-select form-select-sm flex-fill"></select>
                                        <select id="date-end-month" class="form-select form-select-sm flex-fill"></select>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- 真正用於送出資料 -->
                    <input type="hidden" name="report_type" value="{{ request('report') }}">
                    <input type="hidden" name="start_date" id="input-start-date">
                    <input type="hidden" name="end_date" id="input-end-date">
                    <div id="hidden-product-inputs"></div>

                </form>
            </div>

            <div class="col-lg-5 scrollable-column bg-light p-3 position-relative">

                <div class="card shadow-sm mb-3 sticky-top" style="top: 0; z-index: 10;">
                    <div class="card-body p-2 d-flex justify-content-between align-items-center bg-white rounded">
                        <div>
                            <small class="text-muted d-block">時間區段</small>
                            <span id="display-date-range" class="fw-bold text-danger fs-5">--</span>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" id="btn-submit-report" class="btn btn-danger fw-bold">
                                <i class="bi me-1"></i> 製作報表
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm" style="min-height: 500px;">
                    <div class="card-header bg-secondary text-white py-1 px-3 d-flex justify-content-between align-items-center">
                        <small class="fw-bold">已選清單 (<span id="cart-count">0</span>)</small>
                        <button id="btn-clear-cart" class="btn btn-warning btn-xs btn-outline-light py-0" style="font-size: 12px; color:dimgray">清空</button>
                    </div>

                    <div class="table-responsive">
                        <table style="font-size:14px;" class="table table-sm table-hover table-striped mb-0 table-sticky-header">
                            <thead>
                                <tr>
                                    <th style="width: 30px;">序</th>
                                    <th style="width: 60px;">代號</th>
                                    <th>品名</th>
                                    <th style="width: 60px;" class="text-center">操作</th>
                                    <th style="width: 20px;" class="text-center"></th>
                                </tr>
                            </thead>
                            <tbody id="product-cart-body" class="small">
                                </tbody>
                        </table>
                    </div>
                    <div id="cart-empty-msg" class="text-center text-muted py-5 mt-5">
                        <i class="bi bi-basket3 fs-1 d-block mb-2"></i>
                        尚未選擇任何商品
                    </div>
                </div>

            </div>
        </div>
    </div>

    <input type="hidden" id="current-report-type" value="{{ request('report') }}">

    <x-select2-library />
    @vite(['resources/js/analysis.js'])

</body>
</html>
