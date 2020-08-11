<?php

namespace App\Http\Controllers\User;

use App\Area;
use App\Category;
use App\Notifications\NewUserPost;
use App\Tag;
use App\Post;
use App\User;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $posts = Auth::user()->posts()->latest()->get();
        foreach($posts as $key => $value){
            $location = DB::table('areas')->select('area_name')->where('id', $posts[$key]->location)->first();
            $posts[$key]->location = $location ?? "";
        }
        return view('user.post.index',compact('posts'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $categories = Category::all();
        $tags = Tag::all();
        $areas = DB::table('areas')->orderBy('area_name')->get();
        return view('user.post.create',compact('categories','tags', 'areas'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $post = new Post();

        $this->validate($request,[
            'title' => 'required',
            'image' => 'required',
            'categories' => 'required',
            'tags' => 'required',
            'body' => 'required',
            'location' => 'required'
        ]);
        $image = $request->file('image');
        $img = array();
        $slug = str_slug($request->title);
        if(isset($image))
        {
            foreach($image as $key => $value){
//            make unipue name for image
            $currentDate = Carbon::now()->toDateString();
            $imageName  = $slug.'-'.$currentDate.'-'.uniqid().'.'.$image[$key]->getClientOriginalExtension();

            if(!Storage::disk('public')->exists('post'))
            {
                Storage::disk('public')->makeDirectory('post');
            }

            $postImage = Image::make($image[$key])->resize(1600,1066)->save($imageName);
            Storage::disk('public')->put('post/'.$imageName,$postImage);
            array_push($img, $imageName);
        }
        // dd(count($img));
     } else {
            $imageName = "default.png";
        }
        $post->user_id = Auth::id();
        $post->title = $request->title;
        $post->slug = $slug;
        if(count($image) > 1){
            $post->image = implode(",",$img);
        } else{
            $post->image = $imageName;
        }
        $post->body = $request->body;
        $post->location = $request->location;
        if(isset($request->status))
        {
            $post->status = true;
        }else {
            $post->status = false;
        }
        $post->is_approved = false;
        $post->save();

        $post->categories()->attach($request->categories);
        $post->tags()->attach($request->tags);
        // $post->areas()->attach($request->location);

        $users = User::where('role_id','1')->get();
        Notification::send($users, new NewUserPost($post));
        Toastr::success('Post Successfully Saved :)','Success');
        return redirect()->route('user.post.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function show(Post $post)
    {
        if ($post->user_id != Auth::id())
        {
            Toastr::error('You are not authorized to access this post','Error');
            return redirect()->back();
        }
        return view('user.post.show',compact('post'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function edit(Post $post)
    {
        if ($post->user_id != Auth::id())
        {
            Toastr::error('You are not authorized to access this post','Error');
            return redirect()->back();
        }
        $categories = Category::all();
        $tags = Tag::all();
        $areas = DB::table('areas')->orderBy('area_name')->get();
        return view('user.post.edit',compact('post','categories','tags', 'areas'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Post $post)
    {
        if ($post->user_id != Auth::id())
        {
            Toastr::error('You are not authorized to access this post','Error');
            return redirect()->back();
        }
        $this->validate($request,[
            'title' => 'required',
            'image' => 'image',
            'categories' => 'required',
            'tags' => 'required',
            'body' => 'required',
            'location' => 'required'
            ]);
            $image = $request->file('image');
            // dd(count($image));
            $img = array();
            $slug = str_slug($request->title);
            if(!empty($image) && count($image) > 0)
            {
            foreach($image as $key => $value){
//            make unipue name for image
            $currentDate = Carbon::now()->toDateString();
            $imageName  = $slug.'-'.$currentDate.'-'.uniqid().'.'.$image[$key]->getClientOriginalExtension();

            if(!Storage::disk('public')->exists('post'))
            {
                Storage::disk('public')->makeDirectory('post');
            }

            $postImage = Image::make($image[$key])->resize(1600,1066)->save($imageName);
            Storage::disk('public')->put('post/'.$imageName,$postImage);
            array_push($img, $imageName);
        }
     } else {
            $imageName = "default.png";
        }

        $post->user_id = Auth::id();
        $post->title = $request->title;
        $post->slug = $slug;
        if(!empty($image) && count($image) > 0){
            $post->image = implode(",",$img);
        }
        $post->body = $request->body;
        $post->location = $request->location;
        if(isset($request->status))
        {
            $post->status = true;
        }else {
            $post->status = false;
        }
        $post->is_approved = false;
        $post->save();

        $post->categories()->sync($request->categories);
        $post->tags()->sync($request->tags);
        // $post->area()->sync($request->location);
        Toastr::success('Post Successfully Updated :)','Success');
        return redirect()->route('user.post.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function destroy(Post $post)
    {
        if ($post->user_id != Auth::id())
        {
            Toastr::error('You are not authorized to access this post','Error');
            return redirect()->back();
        }
        if (Storage::disk('public')->exists('post/'.$post->image))
        {
            Storage::disk('public')->delete('post/'.$post->image);
        }
        $post->categories()->detach();
        $post->tags()->detach();
        $post->delete();
        Toastr::success('Post Successfully Deleted :)','Success');
        return redirect()->back();
    }
}
