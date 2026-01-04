<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\SavedPost;
use Illuminate\Http\Request;

class SavedPostController extends Controller
{
    public function index(Request $request,$id)
    {
        $savedPosts = SavedPost::where("user_id", "=", $id)->get();
        $postsId = $savedPosts->pluck("post_id");
        // Exclude draft posts from saved posts
        $posts = Post::whereIn("id",$postsId)->where("status", "!=", "draft")->get();
        return response(PostResource::collection($posts),200);
    }
    public function store(Request $request)
    {
        $data = $request->validate([
            "post_id" => "required|exists:posts,id",
            "user_id" => "required|exists:users,id"
        ]);

        $post = Post::find($data["post_id"]);

        if($post->user_id == $data["user_id"])
            return response(["message" => "You can't save your own posts"],403);

        $savedPost = SavedPost::create([
            "post_id" => $data["post_id"],
            "user_id" => $data["user_id"]
        ]);

        return response($savedPost,200);
    }
    public function destroy(Request $request)
    {
         $data = $request->validate([
            "post_id" => "required|exists:posts,id",
            "user_id" => "required|exists:users,id"
        ]);

        $query = SavedPost::query();
        $query->where("user_id", "=",$data["user_id"]);
        $query->where("post_id", "=",$data["post_id"]);
        $savedPost = $query->delete();
        return response("",204);
    }
    public function isPostSaved(Request $request)
    {
        $post = SavedPost::where([
            "post_id" => $request->post_id,
            "user_id" => $request->user_id
        ])->exists();

        return response()->json(["saved" => $post]);
    }
}
