<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 確保 jQuery 已載入
        if (window.jQuery) {
            // 設定 Bootstrap 5 主題與繁體中文
            $.fn.select2.defaults.set("theme", "bootstrap-5");
            $.fn.select2.defaults.set("language", "zh-TW");
            $.fn.select2.defaults.set("placeholder", "請輸入關鍵字搜尋...");
            $.fn.select2.defaults.set("allowClear", true);
            $.fn.select2.defaults.set("width", "100%");
        }
    });
</script>
