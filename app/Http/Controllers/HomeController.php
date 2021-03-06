<?php

namespace App\Http\Controllers;

use App\Category;
use App\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $categories = Category::all();
        $posts = Post::latest()->approved()->published()->paginate(10);
        foreach($posts as $key => $value){
            $location = DB::table('areas')->select('area_name')->where('id', $posts[$key]->location)->first();
            $posts[$key]->location = $location ?? "";
        }
        $recentposts = Post::latest()->approved()->published()->get();
        $areas =  DB::table('areas')->orderBy('area_name')->get();
        return view('welcome',compact('categories','posts','recentposts', 'areas'));
    }
}
