<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class UserController extends Controller
{
    /**
     * Test endpoint.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * Get the authenticated user's profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $profilePictureUrl = $user->getFirstMediaUrl('profile_pictures'); // Get the profile picture URL

        return response()->json([
            'status' => true,
            'data' => [
                'user' => $user,
                'profile_picture' => $profilePictureUrl,
            ],
        ], 200);
    }

    /**
     * Update the authenticated user's profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $request->user()->id,
            'bio' => 'nullable|string',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'password' => 'sometimes|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 400);
        }

        // Get the authenticated user
        $user = $request->user();

        // Handle file upload using Media Library
        if ($request->hasFile('profile_picture')) {
            // Delete old profile picture if it exists
            $user->clearMediaCollection('profile_pictures');

            // Upload new profile picture
            $user->addMediaFromRequest('profile_picture')
                ->toMediaCollection('profile_pictures');
        }

        // Create an array for update data
        $updateData = $request->only(['name', 'email', 'bio']);

        // Update password if provided
        if ($request->has('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        // Update the user
        $user->update($updateData);

        // Get the updated profile picture URL
        $profilePictureUrl = $user->getFirstMediaUrl('profile_pictures');

        return response()->json([
            'status' => true,
            'data' => [
                'name' => $user->name,
                'email' => $user->email,
                'bio' => $user->bio,
                'profile_picture' => $profilePictureUrl,
            ],
        ], 200);
    }

    /**
     * Register a new user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'bio' => 'nullable|string',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 400);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'bio' => $request->bio,
        ]);

        // Handle profile picture upload
        if ($request->hasFile('profile_picture')) {
            $user->addMediaFromRequest('profile_picture')
                ->toMediaCollection('profile_pictures');
        }

        // Get the profile picture URL
        $profilePictureUrl = $user->getFirstMediaUrl('profile_pictures');

        // Generate a token for the user
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'data' => [
                'user' => $user,
                'token' => $token,
                'profile_picture' => $profilePictureUrl,
            ],
        ], 201);
    }

    /**
     * Log in a user and return a token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 400);
        }

        // Attempt to authenticate the user
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found',
                ], 404);
            } else if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'password is incorrect',
                ], 400);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid credentials',
                ], 400);
            }
        }

        // Generate a token for the user
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ]);
    }

    /**
     * Log out the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // Revoke the user's token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    public function destroy(Request $request)
    {
        // Get the authenticated user
        $user = $request->user();

        // Delete the user's profile picture (media) if it exists
        if ($user->hasMedia('profile_pictures')) {
            $user->clearMediaCollection('profile_pictures');
        }

        // Delete the user
        $user->delete();

        return response()->json([
            'status' => true,
            'message' => 'User and associated profile picture deleted successfully',
        ], 200);
    }

}