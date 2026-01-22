import './bootstrap';

// 定義全域變數，用來存放「已選商品」
// 格式: [{ id: '3120123', text: '3120123 古道梅子綠茶' }, ...]
let productCart = [];
let brandProductsCache = {};// 該品牌商品快取

$(document).ready(function() {

    // ==========================================
    // 1. 初始化與報表類型判斷
    // ==========================================
    const reportType = $('#current-report-type').val();

    // 如果是「品群實銷金額 (PSD)」，則不需要選商品，隱藏左上角的商品篩選區
    if (reportType === 'category-psd') {
        $('.card-header:contains("1. 商品選擇")').closest('.card').hide();
        $('.card-header:contains("已選清單")').closest('.card').hide();
        // 也可以考慮在這邊自動加入那 19 個固定品群到 hidden input，或者後端直接處理
    }

    // 單品詳細資料：切換為單一月份模式
    const isSingleMonthMode = (reportType === 'product-detail');
    if (isSingleMonthMode) {
        $('#row-date-end').hide(); // 隱藏結束時間
        $('#label-date-start').text('查詢月份：'); // 修改標籤文字
    }

    // ==========================================
    // 2. 購物車邏輯
    // ==========================================

    // 加入商品到購物車 (支援單一或批次)
    window.addToCart = function(products) {
        if (!Array.isArray(products)) {
            products = [products];
        }

        let addedCount = 0;
        products.forEach(p => { //相當於products.forEach(function(p ,index, array){
            //沒有id的選項不加入
            if (!p.id) return;
            // 檢查是否已存在 (避免重複)
            if (!productCart.some(item => item.id === p.id)) {
                productCart.push(p);
                addedCount++;
            }
        });

        if (addedCount > 0) {
            renderCart();
        }
    };

    // 渲染購物車表格
    function renderCart() {
        const tbody = $('#product-cart-body');
        tbody.empty();

        if (productCart.length === 0) {
            $('#cart-empty-msg').show();
            $('.table-responsive table').hide();
        } else {
            $('#cart-empty-msg').hide();
            $('.table-responsive table').show();

            productCart.forEach((p, index) => {
                const tr = `
                    <tr data-id="${p.id}">
                        <td>${index + 1}</td>
                        <td>${p.id}</td>
                        <td>${p.text.split(' ')[1] || p.text}</td> <td class="text-center">
                            <i class="bi bi-arrow-up-short btn-sort" onclick="moveItem(${index}, -1)"></i>
                            <i class="bi bi-arrow-down-short btn-sort" onclick="moveItem(${index}, 1)"></i>
                        </td>
                        <td>
                            <i class="bi bi-x text-danger btn-sort ms-1" onclick="removeItem(${index})"></i>
                        </td>
                    </tr>
                `;
                tbody.append(tr);
            });
        }

        // 更新計數
        $('#cart-count').text(productCart.length);

        // 更新 Hidden Inputs (這才是真正要送出給後端的資料)
        updateHiddenInputs();
    }

    // 更新隱藏欄位 (給 Form 使用)
    function updateHiddenInputs() {
        const container = $('#hidden-product-inputs');
        container.empty();
        productCart.forEach(p => {
            container.append(`<input type="hidden" name="product_codes[]" value="${p.id}">`);
        });
    }

    // 移除商品 (掛載到 window 讓 HTML onclick 呼叫)
    window.removeItem = function(index) {
        productCart.splice(index, 1);
        renderCart();
    };

    // 移動排序 (direction: -1 上移, 1 下移)
    window.moveItem = function(index, direction) {
        const newIndex = index + direction;
        if (newIndex >= 0 && newIndex < productCart.length) {
            // 交換陣列元素
            [productCart[index], productCart[newIndex]] = [productCart[newIndex], productCart[index]];
            renderCart();
        }
    };

    // 清空按鈕
    $('#btn-clear-cart').click(function(e) {
        e.preventDefault();
        if (confirm('確定要清空清單嗎？')) {
            productCart = [];
            renderCart();
        }
    });


    // ==========================================
    // 3. 商品篩選區 (左側工具)
    // ==========================================

    // A. 三層連動選單
    // ----------------------------

    // 初始化 Select2
    $('#select-cat1').select2({ placeholder: '讀取中...' });
    $('#select-cat2').select2({ placeholder: '讀取中...' }); // 預設載入全部
    $('#select-product-quick').select2({ placeholder: '請先選擇群號', closeOnSelect: false });
    $('#select-brand-products').select2({ placeholder: '請先選擇品牌', closeOnSelect: false });

    // 載入品號 (Cat1)
    axios.get('/search/cats/1').then(res => {
        const data = res.data.results;
        // 轉換格式給 Select2 (它需要 id, text)
        // 並加入一個空白選項 placeholder
        const options = data.map(item => ({ id: item.id, text: item.id }));
        options.unshift({ id: '', text: '' });

        $('#select-cat1').select2({
            data: options,
            placeholder: '選擇品號'
        });
    });

    //初始直接載入所有群號，可跳過品號
    loadCat2();
    // 共用的載入 Cat2 函式
    function loadCat2(cat1 = null) {
        // 清空並顯示讀取中
        $('#select-cat2').empty();

        const params = cat1 ? { cat1 } : {}; // 有 cat1 就傳，沒有就傳空 (後端會回傳全部)

        axios.get('/search/cats/2', { params }).then(res => {
            const data = res.data.results;//Controller端return response()->json(['results' => $results])
            const options = data.map(item => ({ id: item.id, text: item.text }));
            options.unshift({ id: '', text: '選擇群號' });

            $('#select-cat2').select2({
                data: options,
                placeholder: '選擇群號'
            });
        });
    }

    // 品號變更事件
    $('#select-cat1').on('change', function() {
        const cat1 = $(this).val();
        $('#select-product-quick').empty().trigger('change'); // 清空單品

        // 重新載入 Cat2 (根據是否選定 cat1 進行篩選)
        loadCat2(cat1);
    });

    // 群號變更 -> 載入單品
    $('#select-cat2').on('change', function() {
        const cat2 = $(this).val();
        $('#select-product-quick').empty().trigger('change');

        if (cat2) {
            axios.get('/search/products-by-cat', { params: { cat2 } }).then(res => {
                const data = res.data.results; // [{id: '312...', text: '312... 名稱'}]

                // const options = data.map(item => ({ id: item.id, text: item.text }));
                // options.unshift({ id:'', text:'點擊加入清單 (可連點)'});
                // [新增] 在最前面插入 Placeholder
                data.unshift({ id: '', text: '選擇單品 (可連點)' });

                $('#select-product-quick').select2({
                    data: data,
                    placeholder: '點擊加入清單 (可連點)',
                    closeOnSelect: false // 關鍵：選完不關閉
                });
            });
        }
    });

    // 單品被點擊 (Select2 事件) -> 加入購物車
    $('#select-product-quick').on('select2:select', function(e) {
        const data = e.params.data;
        addToCart({ id: data.id, text: data.text });

        // 技巧：選完後馬上把這個選項「取消選取」(UI上)，讓使用者感覺像是一個按鈕
        // 但 Select2 設為 multiple 時比較難做「不留痕跡」，這裡僅加入購物車
        // 使用者會看到選單上變灰或打勾
    });


    // B. 萬用搜尋
    // ----------------------------
    $('#select-product-search').select2({
        ajax: {
            url: '/search/products',
            dataType: 'json',
            delay: 250,
            data: params => ({ term: params.term }),
            processResults: data => ({ results: data.results })
        },
        placeholder: '輸入代號或名稱...',
        minimumInputLength: 1
    });

    $('#select-product-search').on('select2:select', function(e) {
        const data = e.params.data;
        addToCart(data);
        $(this).val(null).trigger('change'); // 選完清空搜尋框
    });


    // C. 品牌快選
    // ----------------------------
    $('#select-brand').select2({
        ajax: {
            url: '/search/brands',
            dataType: 'json',
            delay: 250,
            data: params => ({ term: params.term }),
            processResults: data => ({ results: data.results })
        },
        placeholder: '搜尋品牌...'
    });

    $('#select-brand').on('change', function() {
        const brand = $(this).val();
        const targetSelect = $('#select-brand-products');
        targetSelect.empty();

        if (brand) {
            axios.post('/search/products-by-brands', { brands: [brand] }).then(res => {
                const products = res.data;

                // 將該品牌商品存入快取，Key 為品牌名
                brandProductsCache[brand] = products;

                //加入一個空的 Placeholder 選項，確保 select2 不會自動選取第一個
                targetSelect.append('<option></option>');

                // 加入「加入全部」選項
                targetSelect.append(new Option(`➕ 加入全部 ${brand} 商品 (${products.length})`, `ALL_${brand}`, false, false));

                products.forEach(p => {
                    targetSelect.append(new Option(p.text, p.id, false, false));
                });
            });
        }
    });

    // 品牌商品選單變更
$('#select-brand-products').on('change', function() {
        const val = $(this).val();
        if (!val) return;

        // 判斷是否為「加入全部」
        if (val.startsWith('ALL_')) {
            const brand = val.replace('ALL_', '');
            const allProducts = brandProductsCache[brand]; // 從快取拿資料

            if (allProducts && allProducts.length > 0) {
                addToCart(allProducts);
                // alert(`已加入 ${allProducts.length} 項商品`); // 體驗優化：不跳 alert，直接加
            }
        } else {
            const text = $(this).find('option:selected').text();
            addToCart({ id: val, text: text });
        }

        // 選完重置，方便下次再選
        $(this).val(null).trigger('change');
    });


    // ==========================================
    // 4. 時間區段邏輯
    // ==========================================

    // 初始化：從後端撈取可用日期
    let availableDates = {};

    axios.get('/analysis/dates').then(res => {
        availableDates = res.data; // { 2025: [10, 9], 2024: [12...] }
        // 初始化年份選單
        initYearSelect('#date-start-year');
        initYearSelect('#date-end-year');
    });

    function initYearSelect(selector) {
        const $el = $(selector);
        $el.append('<option value="">年</option>');
        Object.keys(availableDates).sort((a,b) => b-a).forEach(year => { //產生一個從大到小 (降序) 的排序
            const rocYear = parseInt(year) - 1911;
            $el.append(`<option value="${year}">${year} (${rocYear})</option>`);
        });
    }

    // 通用：年變更 -> 更新月
    function onYearChange(yearSelectId, monthSelectId) {
        $(yearSelectId).on('change', function() {
            const year = $(this).val();
            const $month = $(monthSelectId);
            $month.empty().append('<option value="">月</option>');

            if (year && availableDates[year]) {
                availableDates[year].forEach(m => {
                    $month.append(`<option value="${m}">${m}月</option>`);
                });
            }
            updateDateDisplay();
        });
    }

    onYearChange('#date-start-year', '#date-start-month');
    onYearChange('#date-end-year', '#date-end-month');
    $('#date-start-month, #date-end-month').on('change', updateDateDisplay);


    // 區間防呆檢查
    function updateDateDisplay() {

        //開始時間
        const sY = $('#date-start-year').val();
        const sY_txt = $('#date-start-year option:selected').text();
        const sM = $('#date-start-month').val();

        // 取得 ROC 年份顯示
        const formatY = (txt) => {
            if (!txt || txt === '年') return '';
            const match = txt.match(/\((\d+)\)/);
            return match ? match[1] + '年' : txt;
        };

        // 單一月份模式邏輯(for單品詳細資料報表)
        if (isSingleMonthMode) {
            const $display = $('#display-date-range');
            $display.removeClass('text-danger text-dark fw-bold').addClass('text-dark fw-bold');

            if (sY && sM) {
                // 顯示文字：113年5月
                $display.text(`${formatY(sY_txt)}${sM}月`);

                // 填入 Hidden Input：Start 和 End 都設為同一個月份
                const dateStr = `${sY}-${sM.toString().padStart(2, '0')}`;
                $('#input-start-date').val(dateStr);
                $('#input-end-date').val(dateStr);
            } else {
                $display.text('--');
                $('#input-start-date').val('');
                $('#input-end-date').val('');
            }
            return; // 單月模式結束，不執行下方區間邏輯
        }

        //結束時間
        const eY = $('#date-end-year').val();
        const eY_txt = $('#date-end-year option:selected').text();
        const eM = $('#date-end-month').val();

        // 1. 基本顯示字串
        let startStr = '??';
        if (sY && sM) startStr = `${formatY(sY_txt)}${sM}月`;

        let endStr = '??';
        if (eY && eM) endStr = `${formatY(eY_txt)}${eM}月`;

        // 檢查區間邏輯
        const $display = $('#display-date-range');
        $display.removeClass('text-danger text-dark fw-bold').addClass('text-dark fw-bold');

        if (sY && sM && eY && eM) {
            // 比大小 (ex: 202505 vs 202412)
            const startVal = parseInt(sY) * 100 + parseInt(sM);
            const endVal = parseInt(eY) * 100 + parseInt(eM);

            if (endVal < startVal) {
                // 錯誤狀況
                $display.text('無效區間 (結束早於開始)').addClass('text-danger');
                $('#input-start-date').val('');
                $('#input-end-date').val('');
                return;
            }
            const startInput = `${sY}-${sM.toString().padStart(2, '0')}`;
            const endInput = `${eY}-${eM.toString().padStart(2, '0')}`;
            $('#input-start-date').val(startInput);
            $('#input-end-date').val(endInput);
        }else {
            $('#input-start-date').val('');
            $('#input-end-date').val('');
        }

        // 正常顯示
        if (startStr !== '??' || endStr !== '??') {
            $display.text(`${startStr} ~ ${endStr}`);
        } else {
            $display.text('--');
        }

        // 填入靜態 Hidden Input
        if (sY && sM && eY && eM) {
            const startStr = `${sY}-${sM.toString().padStart(2, '0')}`;
            const endStr = `${eY}-${eM.toString().padStart(2, '0')}`;
            $('#input-start-date').val(startStr);
            $('#input-end-date').val(endStr);
        } else {
            $('#input-start-date').val('');
            $('#input-end-date').val('');
        }

    }

    // 單一按鈕送出事件
    $('#btn-submit-report').click(function(e) {
        e.preventDefault();

        const start = $('#input-start-date').val();
        const end = $('#input-end-date').val();

        // 檢查日期
        if (!start || !end) {
            alert(isSingleMonthMode ? '請選擇查詢月份！' : '請先選擇完整的時間區段！');
            return;
        }

        // 商品檢查 (如果是品群報表則不用檢查)
        if (reportType !== 'category-psd' && productCart.length === 0) {
            alert('請至少選擇一項商品！');
            return;
        }

        // 直接送出表單 (action/method/target 已在 HTML 定義)
        $('#analysis-form').submit();
    });


});
