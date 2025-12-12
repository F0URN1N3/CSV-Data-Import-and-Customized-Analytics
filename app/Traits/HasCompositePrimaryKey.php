<?php

namespace App\Traits;

trait HasCompositePrimaryKey
{
    /**
     * 取得 Primary Key 的值 (覆寫原生方法以支援陣列)
     */
    public function getKey()
    {
        $keyName = $this->getKeyName();

        if (is_array($keyName)) {
            $attributes = [];
            foreach ($keyName as $key) {
                $attributes[$key] = $this->getAttribute($key);
            }
            return $attributes;
        }

        return parent::getKey();
    }

    /**
     * 取得用於 Save/Update 查詢的 Primary Key 值
     * (解決 Illegal offset type 的關鍵)
     */
    protected function getKeyForSaveQuery()
    {
        $keyName = $this->getKeyName();

        if (is_array($keyName)) {
            $keys = [];
            foreach ($keyName as $key) {
                $keys[$key] = $this->original[$key] ?? $this->getAttribute($key);
            }
            return $keys;
        }

        return parent::getKeyForSaveQuery();
    }

    /**
     * 設定 Update 語句的 Where 條件
     * (讓 Laravel 知道要用 WHERE year=... AND month=... 去更新)
     */
    protected function setKeysForSaveQuery($query)
    {
        $keyName = $this->getKeyName();

        if (is_array($keyName)) {
            foreach ($keyName as $key) {
                $query->where($key, '=', $this->getKeyForSaveQuery()[$key]);
            }
            return $query;
        }

        return parent::setKeysForSaveQuery($query);
    }
}
