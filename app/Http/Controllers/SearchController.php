<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    /**
     * 搜尋品牌 (給 Brand Selector 用)
     * 回傳 Select2 格式: { results: [{id: '古道', text: '古道'}, ...] }
     */
    public function searchBrands(Request $request)
    {
        $term = $request->input('term'); // 使用者輸入的關鍵字

        $query = Product::query()
            ->select('brand')
            ->distinct() // 去除重複，只留唯一的品牌名
            ->whereNotNull('brand')
            ->where('brand', '!=', '');

        if ($term) {
            $query->where('brand', 'like', "%{$term}%");
        }

        // 取前 20 筆符合的就好，避免選單太長
        $brands = $query->limit(20)->pluck('brand');

        // 轉換格式給 Select2
        $results = $brands->map(function ($brand) {
            return [
                'id' => $brand,
                'text' => $brand
            ];
        });

        return response()->json([
            'results' => $results
        ])->setEncodingOptions(JSON_UNESCAPED_UNICODE);//為了讓測試階段直接可以看到中文
    }

    /**
     * 搜尋單品 (給 Product Selector 用)
     * 支援輸入「代號」或「名稱」
     */
    public function searchProducts(Request $request)
    {
        $term = $request->input('term');

        $query = Product::query()
            ->select('product_code', 'name');

        if ($term) {
            $query->where(function ($q) use ($term) {
                $q->where('product_code', 'like', "{$term}%") // 代號：符合開頭即可 (比較快)
                  ->orWhere('name', 'like', "%{$term}%");     // 名稱：模糊搜尋
            });
        }

        $products = $query->limit(20)->get();

        $results = $products->map(function ($product) {
            return [
                'id' => $product->product_code,
                // 顯示格式： "3120123 古道梅子綠茶"
                'text' => $product->product_code . ' ' . $product->name
            ];
        });

        return response()->json([
            'results' => $results
        ])->setEncodingOptions(JSON_UNESCAPED_UNICODE);//為了讓測試階段直接可以看到中文
    }

    /**
     * 根據已選品牌，撈出該品牌下所有商品
     * 用於「品牌 -> 單品」的自動連動
     */
    public function getProductsByBrands(Request $request)
    {
        // 前端會傳來一個陣列： brands[] = '古道' & brands[] = '伯朗'
        $brands = $request->input('brands');

        if (empty($brands) || !is_array($brands)) {
            return response()->json([]);
        }

        $products = Product::query()
            ->select('product_code', 'name')
            ->whereIn('brand', $brands)
            ->get(); // 這裡不設 limit，因為使用者就是要選「全部」

        // 格式一樣要轉成 id/text
        $results = $products->map(function ($product) {
            return [
                'id' => $product->product_code,
                'text' => $product->product_code . ' ' . $product->name
            ];
        });

        return response()->json($results)
            ->setEncodingOptions(JSON_UNESCAPED_UNICODE);//為了讓測試階段直接可以看到中文
    }

    /**
     * API: 取得所有 品號 (Category 1)
     */
    public function getCategories1()
    {
        $cats = Category::where('level', 2)
            ->select('category_code', 'name')
            ->orderBy('category_code')
            ->get();

        $results = $cats->map(function ($c) {
            return [
                'id' => $c->category_code,
                'text' => $c->category_code . ' ' . $c->name // 顯示: "31 茶飲料"
            ];
        });

        return response()->json(['results' => $results])
            ->setEncodingOptions(JSON_UNESCAPED_UNICODE);
    }

    /**
     * API: 根據 品號 取得 群號 (Category 2)
     */
    public function getCategories2(Request $request)
    {
        $cat1 = $request->input('cat1');

        $query = Category::where('level', 3)
            ->select('category_code', 'name')
            ->orderBy('category_code');

        if ($cat1) {
            // 篩選：category_code 開頭必須是 cat1
            // 例如 cat1='31', 則撈 '31%' -> 311, 312...
            $query->where('category_code', 'like', $cat1 . '%');
        }

        $cats = $query->get();

        $results = $cats->map(function ($c) {
            return [
                'id' => $c->category_code,
                'text' => $c->category_code . ' ' . $c->name // 顯示: "311 紅茶"
            ];
        });

        return response()->json(['results' => $results])
            ->setEncodingOptions(JSON_UNESCAPED_UNICODE);
    }

    /**
     * API: 根據 群號 取得 單品
     */
    public function getProductsByCategory(Request $request)
    {
        $cat2 = $request->input('cat2'); // 前端傳來完整三碼: "316"

        $query = Product::select('product_code', 'name')
            ->orderBy('product_code');

        if ($cat2 && strlen($cat2) >= 3) {
            // 拆解邏輯：前2碼是品號，第3碼(含以後)是群號後綴
            $code1 = substr($cat2, 0, 2); // "31"
            $code2 = substr($cat2, 2);    // "6"

            $query->where('category_code_1', $code1)
                  ->where('category_code_2', $code2);
        } else {
            // 防呆：如果沒選群號，回傳空 (避免全撈)
            return response()->json(['results' => []]);
        }

        $products = $query->get();

        $results = $products->map(function ($p) {
            return [
                'id' => $p->product_code,
                'text' => $p->product_code . ' ' . $p->name
            ];
        });

        return response()->json(['results' => $results])
            ->setEncodingOptions(JSON_UNESCAPED_UNICODE);
    }


}
