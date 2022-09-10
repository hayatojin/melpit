<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecondaryCategory extends Model
{
    // 小カテゴリと大カテゴリの1対多のリレーション
    public function primaryCategory()
     {
         return $this->belongsTo(PrimaryCategory::class);
     }
}
