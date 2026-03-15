<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function index()
    {
        $jobs = Job::orderBy('created_at', 'desc')->paginate(12);
        return view('jobs.index', compact('jobs'));
    }

    public function show($slug)
    {
        $job = Job::where('slug', $slug)->firstOrFail();
        return view('jobs.show', compact('job'));
    }
}
