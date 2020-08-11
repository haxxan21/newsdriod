<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $posts = $user->posts;
        foreach($posts as $key => $value){
            $location = DB::table('areas')->select('area_name')->where('id', $posts[$key]->location)->first();
            $posts[$key]->location_area = $location->area_name;
        }
        $popular_posts = $user->posts()
        ->withCount('comments')
        ->withCount('favorite_to_users')
        ->orderBy('view_count','desc')
        ->orderBy('comments_count')
        ->orderBy('favorite_to_users_count')
        ->take(5)->get();
        // echo "<pre>"; print_r($popular_posts); exit;
        $total_pending_posts = $posts->where('is_approved',false)->count();
        $all_views = $posts->sum('view_count');
        return view('user.dashboard',compact('posts','popular_posts','total_pending_posts','all_views'));
    }
}
