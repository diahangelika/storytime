<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StoryController extends Controller
{
    public function getAllStories(Request $request)
    {
        try {
            $search = $request->query('search'); 

            $stories = Story::with([
                'user:id,name,username',
                'category:id,category_name'
            ])
            ->where(function ($query) use ($search) {
                $query->where('title', 'like', '%' . $search . '%')
                      ->orWhereHas('user', function ($subQuery) use ($search) {
                          $subQuery->where('name', 'like', '%' . $search . '%');
                      });
            })
            ->whereNull('deleted_at')->get();

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Stories retrieved successfully',
                'data' => $stories
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => $th->getMessage()
            ]);
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
        $request->validate([
            'title' => 'required|string|max:255',
            'story' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'user_id' => 'required|exists:users,id',
        ]);
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
