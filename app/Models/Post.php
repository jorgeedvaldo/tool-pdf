<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'language', 'description', 'image'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            $post->slug = $post->generateSlug($post->title, $post->id);
            $post->save(); // NOTE: this was here but calling save() inside creating() causes infinite loops or double inserts

            if (empty($post->image)) {
                try {
                    $imageController = new \App\Http\Controllers\ArticleImageController();
                    $post->image = $imageController->generate($post->title);
                    $post->save();
                }
                catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Image generation failed: ' . $e->getMessage());
                }
            }
        });

        static::updating(function ($post) {
            if (empty($post->slug)) {
                $post->slug = \Illuminate\Support\Str::slug($post->title);
            }
        });
    }

    private function generateSlug($title, $id)
    {
        if (static::whereSlug($slug = Str::slug($title))->exists()) {
            $max = static::whereTitle($title)->latest('id');
            $slug = $slug . '-' . $id;
        }
        return $slug;
    }
}
