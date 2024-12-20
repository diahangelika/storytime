<?php

namespace App\Http\Controllers;

use App\Models\Story;
use Illuminate\Http\Request;

class StoryController extends Controller
{
    public function getAllStories(Request $request)
    {
        $search = $request->query('search'); 

        $stories = Story::with(['user', 'category'])
        ->where('title', 'like', '%' . $search . '%')
        ->orWhere('user', function ($query) use ($search) {
            $query->where('name', 'like', '%' . $search . '%');
            // ->orWhere('username', 'like', '%' . $search . '%'); (optional-=)
        })->get();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Stories retrieved successfully',
            'data' => $stories
        ]);
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
}
