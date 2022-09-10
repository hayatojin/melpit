<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\PrimaryCategory;
use Illuminate\Support\Facades\Request;

class Header extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    // ユーザー情報を取得してヘッダーコンポーネントのブレードに渡す
    public function render()
    {
        $user = Auth::user();

        $categories = PrimaryCategory::query()
             ->with([
                 'secondaryCategories' => function ($query) {
                     $query->orderBy('sort_no');
                 }
             ])
             ->orderBy('sort_no')
             ->get();

        // キーワード検索の検索した値を維持する処理
        // inputでname属性のcategoryに入った値を取得 → $defaultsを配列化し、categoryを要素化
        // ブレードに下記を渡し、もし$defaults[category]があれば、selectedを付与する
        $defaults = [
                'category' => Request::input('category', ''),
                'keyword'  => Request::input('keyword', ''),
            ];

        return view('components.header')
             ->with('user', $user)
             ->with('categories', $categories)
             ->with('defaults', $defaults);
    }
}
