<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';
    protected $primaryKey = 'category_code';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    /**
     * 一個品群 (Category) 可以擁有多筆 (HasMany) 兩碼彙總。
     */
    public function summaries2digit(): HasMany
    {
        return $this->hasMany(Category2digitMonthlySummary::class, 'category_code', 'category_code');
    }

    /**
     * 一個品群 (Category) 可以擁有多筆 (HasMany) 三碼統計。
     */
    public function summaries3digit(): HasMany
    {
        return $this->hasMany(Category3digitMonthlySummary::class, 'category_code', 'category_code');
    }

    /**
     * 一個品群 (Category) 可以擁有多筆 (HasMany) 單品統計。
     */
    public function productSummaries(): HasMany
    {
        return $this->hasMany(ProductMonthlySummary::class, 'parent_category_code', 'category_code');
    }
}
