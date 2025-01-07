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
            // フィルターのパラメータを取り出す
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
                ->get();

                $groupedStories = $stories->groupBy('category_id')->map(function ($stories, $categoryId) {
                    $categoryName = $stories->first()->category->category_name ?? null;
            
                    return [
                        'category_id' => $categoryId,
                        'category_name' => $categoryName,
                        'stories' => $stories->map(function ($story) {
                            return [
                                'story_id' => $story->id,
                                'title' => $story->title,
                                'author' => $story->user->name ?? null, // Assuming 'name' is the author's name
                                'content' => $story->content,
                                'cover' => json_decode($story->images, true)[0] ?? null,
                                'author_img' => $story->user->avatar ?? null,
                                'created_at' => $story->created_at->toIso8601String(),
                            ];
                        })->values()->all(),
                    ];
                })->values()->all();

                // ->map(function ($story) {

                //     // ここでカバーのイメージは初めてのイメージにします
                //     $images = json_decode($story->images, true);
                //     $story->cover = $images[0] ?? null;
                //     return $story;
                // });
    
            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Stories retrieved successfully.',
                'data' => $groupedStories,
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
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
                'code' => 201,
                'message' => 'Story created successfully.',
                'data' => $story,
            ], 201);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateStory(Request $request, $id)
    {
        try {
            $request->validate([
                "title" => "required|string|max:255",
                "content" => "required|string",
                "category_id" => "required|exists:categories,id",
                "images" => "array|min:1|max:4",
                "images.*" => "image|mimes:jpeg,png,jpg,gif|max:2048",
            ]);

            $user = JWTAuth::parseToken()->authenticate();

            $story = Story::find($id);

            if (!$story) {
                return response()->json([
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'Story not found',
                ], 404);
            }

            if ($story->user_id !== $user->id) {
                return response()->json([
                    'status' => 'error',
                    'code' => 403,
                    'message' => 'You are not authorized to update this story',
                ], 403);
            }

            // If images are provided, store them and update the image paths
            $imagePaths = json_decode($story->images, true); // Current images

            if ($request->has('images')) {
                $newImages = [];
                foreach ($request->file('images') as $image) {
                    $path = $image->store('stories', 'public');
                    $newImages[] = $path; // Save new image path
                }

                // Merge new images with existing ones
                $imagePaths = array_merge($imagePaths, $newImages);
            }

            // Update the story
            $story->update([
                'title' => $request->title,
                'content' => $request->content,
                'category_id' => $request->category_id,
                'images' => json_encode($imagePaths), // Store as JSON
            ]);

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Story updated successfully.',
                'data' => $story,
            ], 200);

        } catch (\Exception $err) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => $err->getMessage()
            ]);
        }
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
