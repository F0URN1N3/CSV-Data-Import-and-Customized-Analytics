<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Retail Data Analytics - 報表選擇</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .report-card {
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            height: 100%;
        }
        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            border-color: #0d6efd;
        }
        .icon-box { font-size: 3rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
    @include('navigation.nav')

    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold text-secondary">請選擇要產出的報表</h2>
            <p class="text-muted">系統將引導您進入對應的篩選工作站</p>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <a href="{{ route('analysis.query', ['report' => 'category-psd']) }}" class="card report-card text-center p-4 border-info">
                    <div class="card-body">
                        <div class="icon-box text-info"><i class="bi bi-pie-chart"></i></div>
                        <h5 class="card-title fw-bold">品群實銷金額 (PSD)</h5>
                        <p class="card-text small text-muted mt-3">分析品群的實銷金額與 PSD 表現，含去年同期比較。</p>
                    </div>
                </a>
            </div>

            <div class="col-md-6 col-lg-3">
                <a href="{{ route('analysis.query', ['report' => 'product-sales-diff']) }}" class="card report-card text-center p-4 border-success">
                    <div class="card-body">
                        <div class="icon-box text-success"><i class="bi bi-currency-dollar"></i></div>
                        <h5 class="card-title fw-bold">單品實銷金額差異</h5>
                        <p class="card-text small text-muted mt-3">查詢指定單品的銷售金額，計算本期與去年同期的差異金額。</p>
                    </div>
                </a>
            </div>

            <div class="col-md-6 col-lg-3">
                <a href="{{ route('analysis.query', ['report' => 'product-quantity-diff']) }}" class="card report-card text-center p-4 border-success">
                    <div class="card-body">
                        <div class="icon-box text-success"><i class="bi bi-box-seam"></i></div>
                        <h5 class="card-title fw-bold">單品銷售數量差異</h5>
                        <p class="card-text small text-muted mt-3">查詢指定單品的銷售數量，計算本期與去年同期的差異數量。</p>
                    </div>
                </a>
            </div>

            <div class="col-md-6 col-lg-3">
                <a href="{{ route('analysis.query', ['report' => 'product-detail']) }}" class="card report-card text-center p-4 border-warning">
                    <div class="card-body">
                        <div class="icon-box text-warning"><i class="bi bi-list-columns-reverse"></i></div>
                        <h5 class="card-title fw-bold">單品詳細資料</h5>
                        <p class="card-text small text-muted mt-3">調閱單品在特定月份的所有詳細指標 (毛利、店數、廢棄等)。</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</body>
</html>
