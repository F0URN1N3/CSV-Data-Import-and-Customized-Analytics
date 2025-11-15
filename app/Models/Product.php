<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    /**
     * 指定此模型對應的資料表名稱。
     *
     * @var string
     */
    protected $table = 'products';

    /**
     * 指定主鍵 (Primary Key)。
     *
     * @var string
     */
    protected $primaryKey = 'product_code';

    /**
     * 告知 Eloquent 主鍵是否為自動遞增。
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * 告知 Eloquent 主鍵的類型。
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * 設定可被批量賦值的欄位 (Mass Assignment)。
     * 設為空陣列 [] 表示允許所有欄位。
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * 定義此商品擁有的「每月單品統計」關聯。
     * 一個商品 (Product) 可以擁有多筆 (HasMany) 每月統計 (ProductMonthlySummary)。
     */
    public function monthlySummaries(): HasMany
    {
        return $this->hasMany(ProductMonthlySummary::class, 'product_code', 'product_code');
    }
}
