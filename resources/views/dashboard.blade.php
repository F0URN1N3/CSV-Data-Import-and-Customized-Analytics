<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Retail Data Analytics - 資料匯入中心</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    @vite(['resources/js/app.js'])

    <style>
        body {
            background-color: #f8f9fa; /* 淺灰背景，護眼 */
        }
        .card {
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); /* 加一點陰影更有質感 */
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-5px); /* 滑鼠移過去稍微浮起來 */
        }
        /* 遮罩層樣式 */
        #loading-overlay {
            background-color: rgba(0, 0, 0, 0.5); /* 半透明黑底 */
            z-index: 9999;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-5">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <i class="bi bi-graph-up-arrow me-2"></i>
                Retail Data Analytics
            </a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#"><i class="bi bi-cloud-upload me-1"></i> 資料匯入</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('analysis.index') }}">
                            <i class="bi bi-bar-chart-line me-1"></i> 統計分析
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">

        <div class="row mb-4">
            <div class="col-12 text-center">
                <h2 class="fw-bold text-secondary">資料匯入中心</h2>
                <p class="text-muted">請選擇對應的 Excel 報表進行上傳，系統將自動進行清洗與分流處理。</p>
            </div>
        </div>

        <div class="row g-4 justify-content-center">

            <div class="col-md-5">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white py-3">
                        <h5 class="card-title mb-0"><i class="bi bi-box-seam me-2"></i>商品資料 (Master Data)</h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text text-muted small mb-4">
                            對應 <code>products</code> 資料表。<br>包含商品代號、名稱、規格、價格等基礎資訊。
                        </p>
                        <form id="form-products">
                            <div class="mb-3">
                                <label for="file-products" class="form-label">選擇 Excel 檔案 (.xlsx)</label>
                                <input class="form-control" type="file" name="file" id="file-products" accept=".xlsx,.xls">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-upload me-1"></i> 上傳商品主檔
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="card h-100">
                    <div class="card-header bg-success text-white py-3">
                        <h5 class="card-title mb-0"><i class="bi bi-currency-dollar me-2"></i>統計資料 (Monthly Stats)</h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text text-muted small mb-4">
                            對應月度銷售報表。<br>包含全店業績、兩碼/三碼/單品的銷售數據與成長率。
                        </p>
                        <form id="form-stats">
                            <div class="mb-3">
                                <label for="file-stats" class="form-label">選擇 Excel 檔案 (.xlsx)</label>
                                <input class="form-control" type="file" name="file" id="file-stats" accept=".xlsx,.xls">
                            </div>
                            <div class="alert alert-warning py-2 mb-3 d-flex align-items-center" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <div class="small">檔案較大，處理時間約需 1~5 分鐘，請勿關閉視窗。</div>
                            </div>
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-upload me-1"></i> 上傳統計月報
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <footer class="mt-5 py-4 text-center text-muted border-top">
        <small>&copy; 2025 Retail Data Analytics System v1.0</small>
    </footer>

    <div id="loading-overlay" class="position-fixed top-0 start-0 w-100 h-100 d-none d-flex align-items-center justify-content-center">
        <div class="bg-white p-5 rounded shadow text-center" style="width: 400px;">

            <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Loading...</span>
            </div>

            <h4 class="fw-bold text-dark mb-2">資料處理中...</h4>
            <p class="text-muted mb-4 small">正在清洗數據並寫入資料庫，請稍候。</p>

            <div class="progress">
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 100%"></div>
            </div>
            <p class="text-muted mt-2" style="font-size: 12px;">系統正在後台努力搬磚，請勿重新整理頁面</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
