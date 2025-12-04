<?php

namespace App\Models;

use App\Traits\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Category2digitMonthlySummary extends Model
{
    use HasFactory;
    use HasCompositePrimaryKey;

    protected $table = 'category_2digit_monthly_summaries';
    public $incrementing = false;
    protected $primaryKey = ['year', 'month', 'category_code'];
    protected $guarded = [];

    /**
     * 定義此彙總資料隸屬於 (BelongsTo) 哪一個「品群」。
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_code', 'category_code');
    }
}
