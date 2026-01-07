<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-5">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="{{ route('dashboard') }}">
            <i class="bi bi-graph-up-arrow me-2"></i>
            Retail Data Analytics
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                       href="{{ route('dashboard') }}">
                        <i class="bi bi-cloud-upload me-1"></i> 資料匯入
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('analysis.*') ? 'active' : '' }}"
                       href="{{ route('analysis.index') }}">
                        <i class="bi bi-bar-chart-line me-1"></i> 統計分析
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
