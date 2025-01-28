<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;

class StoryController extends Controller
{
    // STORY API
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
                ->orderBy('created_at', 'desc')
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
                                "author_id" => $story->user_id,
                                'author' => $story->user->name ?? null, 
                                'content' => $story->content,
                                'cover' => json_decode($story->images, true)[0] ?? null,
                                'author_img' => $story->user->avatar ?? null,
                                'created_at' => $story->created_at->toIso8601String(),
                            ];
                        })->values()->all(),
                    ];
                })->values()->all();
    
            return response()->json([
                'status' => 'success',
                'message' => 'Stories retrieved successfully.',
                'data' => $groupedStories,
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
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
                'message' => 'Record not found',
            ], 404);
        }

        $story->images = json_decode($story->images, true);

        $similar = Story::with(['user', 'category'])
            ->where('id', '!=', $story->id)
            ->where('category_id', $story->category_id)
            ->orderBy('created_at', 'desc')
            ->get();

        $return = $similar->map(function ($similar) {
            return [
                'id' => $similar->id,
                'title' => $similar->title,
                'content' => $similar->content,
                'cover' => json_decode($similar->images, true)[0] ?? null,
                'category' => $similar->category->category_name,
                'author' => $similar->user->name,
                'author_avatar' => $similar->user->avatar,
                'created_at' => $similar->created_at,
            ];
        })->values()->all();

        if (!$story) {
            return response()->json([
                'status' => 'error',
                'message' => 'Story not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Story retrieved successfully',
            'data' => $story,
            'similar' => $return
        ], 200);
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
    
            // return response
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
        try {
            $request->validate([
                "title" => "required|string|max:255",
                "content" => "required|string",
                "category_id" => "required|exists:categories,id",
                "images" => "sometimes|array|min:1|max:4",
                "images.*" => "sometimes|image|mimes:jpeg,png,jpg,gif|max:2048",
                "prev_images" => "sometimes",
                "deleted_images" => "sometimes",
            ]);

            $user = JWTAuth::parseToken()->authenticate();

            $story = Story::findOrFail($id);

            if (!$story) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Story not found',
                ], 404);
            }

            if ($story->user_id !== $user->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to update this story',
                ], 403);
            }

            $data = [
                'title' => $request->title,
                'content' => $request->content,
                'category_id' => $request->category_id, // Store as JSON
            ];
            $newImages = [];
            if ($request->has('images')) {
                
                foreach ($request->file('images') as $image) {
                    $path = $image->store('stories', 'public');
                    $newImages[] = $path; // Save new image path
                }
            }

            if ($request->has('prev_images')) {
                $prevImages = $request->prev_images ?? [];
                if (is_string($prevImages)) {
                    $prevImages = explode(',', $prevImages);
                }
                if (!is_array($prevImages)) {
                    $prevImages = [];
                }
                // Merge previous and new images
                $allImages = array_merge($prevImages, $newImages);

                // Ensure no more than 4 images
                if (count($allImages) > 4) {
                    return response()->json([
                        'status' => 'error',
                        'code' => 400,
                        'message' => 'You can upload a maximum of 4 images.',
                    ], 400);
                }

                $data['images'] = json_encode($allImages);
            }



            if ($request->has('deleted_images')) {
                $deletedImages = $request->deleted_images ?? [];
                $test['deleted_before'] = json_decode($deletedImages);
            
                // Ensure $deletedImages is a string before applying json_decode
                if (is_string($deletedImages)) {
                    $deletedImages = json_decode($deletedImages, true);
                } elseif (is_array($deletedImages)) {
                    // It's already an array; no further decoding is needed
                } else {
                    // Handle unexpected cases (e.g., null or invalid type)
                    $deletedImages = [];
                }
            
                foreach ($deletedImages as $image) {
                    Storage::disk('public')->delete($image);
                }
            
                // Save the final array of deleted images as a JSON string
                $test['deleted_images'] = json_encode($deletedImages);
            }

            // Update the story
            $story->update($data);

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Story updated successfully.',
                'data' => $story,
                'test' => $test
            ], 200);

        } catch (\Exception $err) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => $err->getMessage()
            ]);
        }
    }

    public function deleteStory($id)
    {
        try {
            
            $user = JWTAuth::parseToken()->authenticate();
            $story = Story::findOrFail($id);

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized',
                ], 401);
            }

            if ($story->user_id !== $user->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Forbidden',
                ], 403);
            }

            $story->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Record deleted successfully.',
            ], 200);

        } catch (\Exception $err) {
            return response()->json([
                'status' => 'error',
                'message' => $err->getMessage()
            ], 500);
        }
    }

    // CATEGORY API
    public function getAllCategories()
    {
        try {
            $category = Category::whereNull('deleted_at')
                ->orderBy('category_name', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Record retrieved successfully',
                'data' => $category
            ], 200);

        } catch (\Exception $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
