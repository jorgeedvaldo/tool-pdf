<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class PostController extends Controller
{
    public function index()
    {
        $locale = App::getLocale();
        $posts = Post::where('language', $locale)
                    ->orderBy('created_at', 'desc')
                    ->paginate(12);
                    
        return view('posts.index', compact('posts'));
    }

    public function show($locale, $slug)
    {
        $post = Post::where('slug', $slug)->where('language', $locale)->firstOrFail();
        
        $recentPosts = Post::where('language', $locale)
                           ->where('id', '!=', $post->id)
                           ->orderBy('created_at', 'desc')
                           ->take(5)
                           ->get();
                           
        return view('posts.show', compact('post', 'recentPosts'));
    }
}
