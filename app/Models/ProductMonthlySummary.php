<?php

namespace App\Models;

use App\Traits\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductMonthlySummary extends Model
{
    use HasFactory;
    use HasCompositePrimaryKey;

    protected $table = 'product_monthly_summaries';
    public $incrementing = false;
    protected $primaryKey = ['year', 'month', 'product_code'];
    protected $guarded = [];

    /**
     * 定義此統計資料隸屬於 (BelongsTo) 哪一個「商品」。
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_code', 'product_code');
    }

    /**
     * 定義此統計資料隸屬於 (BelongsTo) 哪一個「父品群」。
     */
    public function parentCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_category_code', 'category_code');
    }
}
