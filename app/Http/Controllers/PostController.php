<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Validator;

use App\Models\Post;
use Illuminate\Http\Request;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $posts = Post::all();
        $posts->each(function ($post) {
            $post->image_url = $post->getFirstMediaUrl('image_url');
        });

        $response = $posts;
        $response->load('likes');
        $response->load('shares');
        $response->load('user');

        if ($response->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No posts found'
            ], 404);
        }
        return response()->json([
            'status' => true,
            'data' => $response
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request['user_id'] = $request->user()->id;

        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
            'user_id' => 'required|integer|exists:users,id',
            'image_url' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $post = Post::create($request->all());

        // Handle picture upload
        if ($request->hasFile('image_url')) {
            $post->addMediaFromRequest('image_url')
                ->toMediaCollection('image_url');
        }

        // Get the picture URL
        $image = $post->getFirstMediaUrl('image_url');
        $post->image_url = $image;

        $response = $post;


        return response()->json([
            'status' => true,
            'message' => 'Post created successfully',
            'post' => $response,
            'image_ur' => $image
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $response = Post::find($id);
        if (!$response) {
            return response()->json([
                'status' => false,
                'message' => 'Post not found'
            ], 404);
        }
        $response->load('likes');
        $response->load('shares');
        $response->load('user');
        return response()->json([
            'status' => true,
            $response
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json([
                'status' => false,
                'message' => 'Post not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'sometimes|string',
            'image_url' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        // Update post content
        $post->update($request->only('content'));

        // Handle picture upload
        if ($request->hasFile('image_url')) {
            // Delete old image if it exists
            $post->clearMediaCollection('image_url');

            // Upload new image
            $post->addMediaFromRequest('image_url')
                ->toMediaCollection('image_url');
        }

        // Get the updated image URL
        $imageUrl = $post->getFirstMediaUrl('image_url');
        $post->image_url = $imageUrl;
        return response()->json([
            'status' => true,
            'message' => 'Post updated successfully',
            'data' => [
                'post' => $post,
                'image_url' => $imageUrl,
            ]
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json([
                'status' => false,
                'message' => 'Post not found'
            ], 404);
        }

        // Delete associated media
        $post->clearMediaCollection('image_url');

        // Delete the post
        $post->delete();

        return response()->json([
            'status' => true,
            'message' => 'Post deleted successfully'
        ], 200);
    }
}
