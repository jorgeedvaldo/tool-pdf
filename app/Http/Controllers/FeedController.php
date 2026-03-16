<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    public function index()
    {
        $posts = Post::orderBy('created_at', 'desc')->take(20)->get();
        return response()->view('feed', compact('posts'))->header('Content-Type', 'text/xml');
    }
}
