<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    // 出品中
    const STATE_SELLING = 'selling';
    // 購入済み
    const STATE_BOUGHT = 'bought';

    protected $casts = [
        'bought_at' => 'datetime',
    ];

    // bought_atがstring型のため、formatメソッドが使えないエラーの対処
    protected $dates = [
        'bought_at'
    ];

    // 商品に紐づくカテゴリ（小）のリレーション ※商品が多：カテゴリが1
    public function secondaryCategory()
     {
         return $this->belongsTo(SecondaryCategory::class);
     }

     public function seller()
     {
         return $this->belongsTo(User::class, 'seller_id'); // seller_idは外部キー指定
     }
 
     public function condition()
     {
         return $this->belongsTo(ItemCondition::class, 'item_condition_id');
     }

    // 商品が出品中かどうかを返すアクセサ
    public function getIsStateSellingAttribute()
     {
         return $this->state === self::STATE_SELLING; // 定数にアクセスするため、selfメソッドを使う
     }

    // 購入済みかどうかを返すアクセサ
     public function getIsStateBoughtAttribute()
     {
         return $this->state === self::STATE_BOUGHT;
     }
}
