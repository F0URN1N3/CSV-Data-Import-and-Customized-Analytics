@props(['id' => 'brand-select', 'target' => ''])

<div class="mb-3">
    <label for="{{ $id }}" class="form-label fw-bold text-primary">
        <i class="bi bi-shop me-1"></i> 品牌快篩 (複選)
    </label>
    <select id="{{ $id }}" class="form-select select2-brand" name="brands[]" multiple>
        </select>
    <div class="form-text text-muted small">選擇品牌後，系統會自動選取該品牌下所有商品。</div>
</div>

<script type="module">
    // 使用 type="module" 確保在 jQuery 載入後執行，或是直接寫在 app.js
    // 這裡為了方便封裝，先寫簡單的 script
    document.addEventListener('DOMContentLoaded', function () {
        $('#{{ $id }}').select2({
            ajax: {
                url: '/search/brands',
                dataType: 'json',
                delay: 250, // 等使用者打完字再發送，減少請求
                data: function (params) {
                    return { term: params.term }; //Select2 會將這個物件轉換成 URL 參數：?term=使用者輸入的文字。
                },
                processResults: function (data) {
                    // data 是後端回傳的整個 JSON 物件: { "results": [...] }
                    return { results: data.results };
                }
            }
        });

        // 連動邏輯：當品牌改變時
        const targetSelectorId = '{{ $target }}';
        if (targetSelectorId) {
            $('#{{ $id }}').on('change', function () {
                const selectedBrands = $(this).val();

                // 如果清空了品牌，就不做事(或可以選擇清空商品)
                if (!selectedBrands || selectedBrands.length === 0) {
                    return;
                }

                // 呼叫後端 API 撈取商品
                axios.post('/search/products-by-brands', { brands: selectedBrands })
                    .then(response => {
                        const products = response.data;
                        const productSelect = $('#' + targetSelectorId);

                        // 將撈回來的商品「加入」到單品選單中
                        products.forEach(product => {
                            // 檢查是否已經存在，避免重複加入
                            if (productSelect.find("option[value='" + product.id + "']").length) {
                                return;
                            }
                            // 建立新 Option 並設為選取狀態
                            const newOption = new Option(product.text, product.id, true, true);
                            productSelect.append(newOption).trigger('change');
                        });
                    })
                    .catch(error => {
                        console.error('品牌連動失敗:', error);
                        alert('無法載入品牌商品');
                    });
            });
        }
    });
</script>
