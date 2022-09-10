<?php

namespace App\Http\Controllers\MyPage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Auth名前空間のインポートにより、Authという記載だけでクラスを使える
use App\Http\Requests\Mypage\Profile\EditRequest;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class ProfileController extends Controller
{
    public function showProfileEditForm()
    {
        return view('mypage.profile_edit_form')
            ->with('user', Auth::user()); // withメソッド：第一引数はbladeでの変数名、第二引数は変数に格納される値。つまり、userという変数名でログインユーザーの情報を渡している。
    }

    public function editProfile(EditRequest $request)
    {
        $user = Auth::user();

        $user->name = $request->input('name');

        if ($request->has('avatar')) {
            $fileName = $this->saveAvatar($request->file('avatar'));
            $user->avatar_file_name = $fileName;
        }

        $user->save();

        return redirect()->back()
            ->with('status','プロフィールを変更しました。');
    }

    /**
      * アバター画像をリサイズして保存
      *
      * @param UploadedFile $file アップロードされたアバター画像
      * @return string ファイル名
      */
      private function saveAvatar(UploadedFile $file): string
      {
          $tempPath = $this->makeTempPath();
  
          Image::make($file)->fit(200, 200)->save($tempPath); // Intervention Imageのインスタンスを生成し、画像をリサイズし、一時的にファイルパスに保存
  
          $filePath = Storage::disk('public') // ディスクを取得
              ->putFile('avatars', new File($tempPath)); // 画像を保存（storage/app/public配下のavatarsファイルに保存）
  
          return basename($filePath);
      }

      /**
      * 一時的なファイルを生成してパスを返す
      *
      * @return string ファイルパス
      */
     private function makeTempPath(): string
     {
         $tmp_fp = tmpfile(); // /tmpに一時ファイルを作成
         $meta = stream_get_meta_data($tmp_fp); // ファイルのメタ情報を取得
         return $meta["uri"]; // メタ情報からURLを取得
     }
}
