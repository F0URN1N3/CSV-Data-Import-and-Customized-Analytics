import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {

    const formProducts = document.getElementById('form-products');
    const formStats = document.getElementById('form-stats');
    const loadingOverlay = document.getElementById('loading-overlay');

    const handleUpload = async (e, url) => {
        e.preventDefault();

        const form = e.target;
        const fileInput = form.querySelector('input[type="file"]');

        if (!fileInput.files.length) {
            alert('請先選擇檔案！'); // 用瀏覽器原生 alert 就很清楚了
            return;
        }

        const formData = new FormData(form);

        // 1. 顯示 Loading (移除 d-none Class)
        loadingOverlay.classList.remove('d-none');

        try {
            const response = await axios.post(url, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });

            if (response.data.success) {
                alert(response.data.message);
                form.reset();
            }

        } catch (error) {
            console.error(error);
            let msg = '發生未知錯誤';

            if (error.response && error.response.data) {
                msg = error.response.data.message || msg;
                if (error.response.data.errors) {
                    msg += '\n' + JSON.stringify(error.response.data.errors);
                }
            }
            alert('上傳失敗：\n' + msg);

        } finally {
            // 2. 隱藏 Loading (加回 d-none Class)
            loadingOverlay.classList.add('d-none');
        }
    };

    if (formProducts) {
        formProducts.addEventListener('submit', (e) => handleUpload(e, '/import/products'));
    }

    if (formStats) {
        formStats.addEventListener('submit', (e) => handleUpload(e, '/import/stats'));
    }
});
