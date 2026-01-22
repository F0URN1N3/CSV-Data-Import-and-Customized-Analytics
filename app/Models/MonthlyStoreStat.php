<?php

namespace App\Models;

use App\Traits\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyStoreStat extends Model
{
    use HasFactory;
    use HasCompositePrimaryKey;

    protected $table = 'monthly_store_stats';

    /**
     * 告知 Eloquent 此資料表沒有自動遞增的主鍵。
     */
    public $incrementing = false;

    /**
     * 告知 Eloquent 此資料表的主鍵是由多個欄位組成的 (複合主鍵)。
     * 在之後的匯入邏輯中處理 'updateOrCreate'。
     *
     * @var array
     */
    protected $primaryKey = ['year', 'month'];

    /**
     * 允許所有欄位被批量賦值。
     *
     * @var array
     */
    protected $guarded = [];
}
