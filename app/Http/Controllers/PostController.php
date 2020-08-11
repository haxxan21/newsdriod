<?php

namespace App\Http\Controllers;

use App\Area;
use App\Category;
use App\Post;
use App\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class PostController extends Controller
{

    public function index()
    {
        $posts = Post::latest()->approved()->published()->paginate(6);
        $recentposts = Post::latest()->approved()->published()->get();
        return view('posts',compact('posts', 'recentposts'));
    }
    public function details($slug)
    {
        $post = Post::where('slug',$slug)->approved()->published()->first();
        $post->location = DB::table('areas')->select('area_name')->where('id', $post->location)->first();
        $blogKey = 'blog_' . $post->id;
        if (!Session::has($blogKey)) {
            $post->increment('view_count');
            Session::put($blogKey,1);
        }
        $randomposts = Post::approved()->published()->take(3)->inRandomOrder()->get();
        return view('post',compact('post','randomposts'));

    }

    public function postByCategory($slug)
    {
        $category = Category::where('slug',$slug)->first();
        $posts = $category->posts()->approved()->published()->get();
        return view('category',compact('category','posts'));
    }

    public function postByTag($slug)
    {
        $tag = Tag::where('slug',$slug)->first();
        $posts = $tag->posts()->approved()->published()->get();
        return view('tag',compact('tag','posts'));
    }

    public function postByLocation($slug)
    {
        $location = Area::where('id',$slug)->first();
        $posts = Post::where('location', $slug)->approved()->published()->get();
        // dd($posts);
        return view('location',compact('location','posts'));
    }
}
