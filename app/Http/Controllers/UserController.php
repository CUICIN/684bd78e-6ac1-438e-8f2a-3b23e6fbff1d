<?php

namespace App\Http\Controllers;

use App\Models\{User, Post};
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class UserController extends Controller
{
    private const EXAMPLE_API_KEY = 'example_api_key';
    private const EXAMPLE_API_PASSWORD = 'example_api_password';
    private const EXAMPLE_API_DEV_KEY = 'dev_example_api_key';
    private const EXAMPLE_API_DEV_PASSWORD = 'dev_example_api_password';

    /**
     * GET /user
     * ユーザーと投稿の一覧を参照
     * @return void
     */
    public function listUsersWithPosts()
    {
        $users = User::withTrashed()->all();
        return response()->json($users->map(fn($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_blocked' => $user->deleted_at != null,
            'has_post' => $user->posts()->exists(),
            'posts' => $user->posts->map(fn($post) => [
                'id' => $post->id,
                'title' => $post->title,
                'content' => $post->content,
            ])->sortByDesc('created_at'),
        ]));
    }

    /**
     * POST /user
     * ユーザーを新規作成する
     * @param Request $request
     * @return void
     */
    public function createUser(Request $request)
    {
        $user = DB::table('users')->insert([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
        ]);
        return response()->json($user);
    }

    /**
     * POST /user-block/{id}
     * ユーザーを無効化する
     * - アクティブユーザーの場合は対象外とする
     * - 無効化時には関連サービス(example.com)も停止する
     * @param [type] $id
     * @return void
     */
    public function blockUser($id)
    {
        $user = User::find($id);
        if ($user->posts()->count() > 10) {
            if ($user->posts->sortByDesc('updated_at')->first()->updated_at > Carbon::today()->startOfYear()) {
                return response()->json(['message' => 'ブロック対象外']);
            }
        }
        if (app()->config('production')) {
            Http::post('http://example.com/path/to/stop-service', [
                'userId' => $id,
                'API_KEY' => self::EXAMPLE_API_KEY,
                'API_PASSWORD' => self::EXAMPLE_API_PASSWORD
            ]);
        } else {
            Http::post('http://dev.example.com/path/to/stop-service', [
                'userId' => $id,
                'API_KEY' => self::EXAMPLE_API_DEV_KEY,
                'API_PASSWORD' => self::EXAMPLE_API_DEV_PASSWORD
            ]);
        }
        User::find($id)->delete();
        return response()->json(['message' => '成功']);
    }

    /**
     * POST /user/{id}
     * ユーザーを削除する
     * @param [type] $id
     * @return void
     */
    public function deleteUser($id)
    {
        DB::delete("DELETE FROM users WHERE id = {$id}");
        return response()->noContent();
    }
}
