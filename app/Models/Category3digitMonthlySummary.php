<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Category3digitMonthlySummary extends Model
{
    use HasFactory;

    protected $table = 'category_3digit_monthly_summaries';
    public $incrementing = false;
    protected $primaryKey = ['year', 'month', 'category_code'];
    protected $guarded = [];

    /**
     * 定義此統計資料隸屬於 (BelongsTo) 哪一個「品群」。
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_code', 'category_code');
    }
}
