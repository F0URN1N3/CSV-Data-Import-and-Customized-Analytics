@props(['id' => 'product-select', 'name' => 'product_codes[]'])

<div class="mb-3">
    <label for="{{ $id }}" class="form-label fw-bold text-success">
        <i class="bi bi-box-seam me-1"></i> 商品選擇 (可複選)
    </label>
    <select id="{{ $id }}" name="{{ $name }}" class="form-select select2-product" multiple>
        </select>
</div>

<script type="module">
    document.addEventListener('DOMContentLoaded', function () {
        $('#{{ $id }}').select2({
            ajax: {
                url: '/search/products',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { term: params.term }; //params.term 是 Select2 提供的使用者在輸入框中鍵入的文字。後端將收到 GET /search/products?term=使用者輸入 這樣的請求。
                },
                processResults: function (data) {
                    return { results: data.results };
                }
            },
            minimumInputLength: 1, // 至少輸入 1 個字才搜尋
            language: {
                inputTooShort: function () {
                    return "請輸入商品代號或名稱...";
                }
            }
        });
    });
</script>
