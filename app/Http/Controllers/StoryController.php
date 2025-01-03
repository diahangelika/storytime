<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class StoryController extends Controller
{
    public function getAllStories(Request $request)
    {

        try {
            // Retrieve filter parameters
            $category = $request->query('category');
            $user = $request->query('user');
            $title = $request->query('title');
    
            // Fetch stories with filters and eager load relationships
            $stories = Story::with(['category', 'user'])
                ->when($category, function ($query) use ($category) {
                    $query->where('category_id', $category);
                })
                ->when($user, function ($query) use ($user) {
                    $query->where('user_id', $user);
                })
                ->when($title, function ($query) use ($title) {
                    $query->where('title', 'like', '%' . $title . '%');
                })
                ->get()
                ->map(function ($story) {
                    // Add a 'cover' property to each story with the first image
                    $images = json_decode($story->images, true);
                    $story->cover = $images[0] ?? null;
                    return $story;
                });
    
            return response()->json([
                'status' => 'success',
                'message' => 'Stories retrieved successfully.',
                'data' => $stories,
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }

        // try {
        //     $search = $request->query('search'); 

        //     $stories = Story::with([
        //         'user:id,name,username',
        //         'category:id,category_name'
        //     ])
        //     ->where(function ($query) use ($search) {
        //         $query->where('title', 'like', '%' . $search . '%')
        //               ->orWhereHas('user', function ($subQuery) use ($search) {
        //                   $subQuery->where('name', 'like', '%' . $search . '%');
        //               });
        //     })
        //     ->whereNull('deleted_at')->get();

        //     return response()->json([
        //         'status' => 'success',
        //         'code' => 200,
        //         'message' => 'Stories retrieved successfully',
        //         'data' => $stories
        //     ]);

        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'status' => 'error',
        //         'code' => 500,
        //         'message' => $th->getMessage()
        //     ]);
        // }
    }

    public function getStoryById($id)
    {
        $story = Story::with(['user', 'category'])->find($id);

        if (!$story) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Story not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Story retrieved successfully',
            'data' => $story
        ]);
    }

    public function createStory(Request $request)
    {
        try {
            // Validate the incoming request
            $request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'category_id' => 'required|exists:categories,id',
                'images' => 'required|array|min:1|max:4',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);
    
            // Get authenticated user
            $user = JWTAuth::parseToken()->authenticate();
    
            // Store images and collect their paths
            $imagePaths = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('stories', 'public');
                $imagePaths[] = $path; // Save image path
            }
    
            // Create a story record
            $story = Story::create([
                'title' => $request->title,
                'content' => $request->content,
                'category_id' => $request->category_id,
                'user_id' => $user->id,
                'images' => json_encode($imagePaths), // Store as JSON
            ]);
    
            return response()->json([
                'status' => 'success',
                'message' => 'Story created successfully.',
                'data' => $story,
            ], 201);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateStory(Request $request, $id)
    {

    }

    public function deleteStory(Request $request, $id)
    {

    }


    public function getAllCategories()
    {
        try {
            $category = Category::whereNull('deleted_at')->get();

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Categories retrieved successfully',
                'data' => $category
            ]);
        } catch (\Exception $th) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => $th->getMessage()
            ]);
        }
    }
}
