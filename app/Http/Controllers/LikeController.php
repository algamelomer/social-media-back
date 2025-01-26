<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Validator;

use App\Models\Like;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function like(Request $request, string $id)
    {
        $request['user_id'] = $request->user()->id;
        $request['post_id'] = $id;

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'post_id' => 'required|integer|exists:posts,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $like = Like::create($request->all());

        return response()->json([
            'status' => true,
            'data' => $like
        ], 201);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function unlike(Request $request, string $id)
    {
        $request['user_id'] = $request->user()->id;
        $request['post_id'] = $id;

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'post_id' => 'required|integer|exists:posts,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $like = Like::where('user_id', $request->user()->id)
            ->where('post_id', $id)
            ->first();

        if (!$like) {
            return response()->json([
                'status' => false,
                'message' => 'Like not found'
            ], 404);
        }

        $like->delete();

        return response()->json([
            'status' => true,
            'message' => 'Like removed'
        ], 200);
    }
}
