<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Payjp\Charge;

class ItemsController extends Controller
{
    public function showItems(Request $request)
     {
        $query = Item::query(); // リクエストに入ってきたクエリ文字列を取得している？
 
         // カテゴリで絞り込み
         if ($request->filled('category')) {
             list($categoryType, $categoryID) = explode(':', $request->input('category'));
 
             if ($categoryType === 'primary') {
                 $query->whereHas('secondaryCategory', function ($query) use ($categoryID) {
                     $query->where('primary_category_id', $categoryID);
                 });
             } else if ($categoryType === 'secondary') {
                 $query->where('secondary_category_id', $categoryID);
             }
         }

         // キーワードで絞り込み
         if ($request->filled('keyword')) { // パラメータがあるか調べる
            $keyword = '%' . $this->escape($request->input('keyword')) . '%'; // キーワードに入った値を、部分一致にする
            $query->where(function ($query) use ($keyword) { 
                $query->where('name', 'LIKE', $keyword); // nameにキーワードが入っているか、descriptionにキーワードが入っているか、OR条件にする
                $query->orWhere('description', 'LIKE', $keyword);
            });
        }

        /*
          state（商品状態）カラムには、出品中・購入済みと２種類のデータが入る可能性があるが、
          出品中の値が入っている方を、先に表示させたい場合の処理
        */
        $items = $query->orderByRaw( "FIELD(state, '" . Item::STATE_SELLING . "', '" . Item::STATE_BOUGHT . "')" ) // FIELD関数で取得順番を決定
             ->orderBy('id', 'DESC') // orderByRaw実施後、出品中の商品と購入済みの商品の中でさらに並べ替え
             ->paginate(52); // 1ページあたりに表示する要素の数を定義
 
         return view('items.items')
             ->with('items', $items);
     }

    // 意図しないキーワードをエスケープ
     private function escape(string $value)
     {
         return str_replace(
             ['\\', '%', '_'],
             ['\\\\', '\\%', '\\_'],
             $value
         );
     }

    // 商品詳細画面の表示
     public function showItemDetail(Item $item) // $itemでルートパラメータを受け取っている
     {
         return view('items.item_detail')
             ->with('item', $item);
     }

     public function showBuyItemForm(Item $item)
     {
         if (!$item->isStateSelling) {
             abort(404);
         }
 
         return view('items.item_buy_form')
             ->with('item', $item);
     }

    // 商品の購入処理
     public function buyItem(Request $request, Item $item)
     {
         $user = Auth::user();
 
         if (!$item->isStateSelling) {
             abort(404);
         }

         $token = $request->input('card-token');

    // 決済処理で例外が発生した場合の処理
     try{
        $this->settlement($item->id, $item->seller->id, $user->id, $token);
     } catch(\Exception $e){
        Log::error($e);
        return redirect()->back()
            ->with('type', 'danger')
            ->with('message', '購入処理が失敗しました。');
     }

     return redirect()->route('item', [$item->id])
             ->with('message', '商品を購入しました。');
     }

    // 商品の決済処理
     private function settlement($itemID, $sellerID, $buyerID, $token)
     {
        // トランザクションを張って商品データ・会員データを更新
        DB::beginTransaction();

        try{
            $seller = User::lockForUpdate()->find($sellerID); // lockForUpdateでテーブルをロック
            $item = Item::lockForUpdate()->find($itemID);

            // すでに商品が購入済みなら、エラーを返す
            if($item->isStateBought){ 
                throw new \Exception('多重決済');
            }

            // 商品テーブルのデータを更新
            $item->state = Item::STATE_BOUGHT;
            $item->bought_at = Carbon::now();
            $item->buyer_id  = $buyerID;
            $item->save();

            $seller->sales += $item->price;
            $seller->save();

            $charge = Charge::create([
                'card'     => $token,
                'amount'   => $item->price,
                'currency' => 'jpy'
            ]);
            if (!$charge->captured) {
                throw new \Exception('支払い確定失敗');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        DB::commit();
    }
}
