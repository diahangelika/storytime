<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Story;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class BookmarkController extends Controller
{
    public function addBookmark($id)
    {
         try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'code' => 401,
                    'message' => 'Unauthorized',
                ], 401);
            }

            $story = Story::findOrFail($id);
            $exist = Bookmark::where('user_id', $user->id)
                ->where('story_id', $story->id)
                ->exist();

            if ($exist) {
                return response()->json([
                    'status' => 'error',
                    'code' => 409,
                    'message' => 'Story Already Exist in Your Bookmark',
                ]);
            }

            Bookmark::create([
                'user_id' => $user->id,
                'story_id' => $story->id
            ]);

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Bookmark Added successfully.',
            ], 200);

         } catch (\Exception $err) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => $err->getMessage()
            ]);
         }
    }

    public function deleteBookmark($id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $bookmark = Bookmark::findOrFail($id);

            if ($user->id !== $bookmark->user_id) {
                return response()->json([
                    'status' => 'error',
                    'code' => 403,
                    'message' => 'You are not authorized to delete this bookmark',
                ], 403);
            }

            $bookmark->delete();

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Bookmark deleted successfully.',
            ], 200);

        } catch (\Exception $err) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => $err->getMessage()
            ]);
        }
    }

    public function bookmark(Request $request)
    {
        try {
            
            $user = JWTAuth::parseToken()->authenticate();

            $request->validate([
                'story_id' => 'required|exists:stories,id',
            ]);
            
            $exist = Bookmark::where('user_id', $user->id)
                ->where('story_id', $request->story_id)
                ->first();

            if ($exist) {
                $exist->delete();
                return response()->json([
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'Bookmark deleted successfully.',
                ], 200);
            } else {
                Bookmark::create([
                    'user_id' => $user->id,
                    'story_id' => $request->story_id
                ]);
                return response()->json([
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'Bookmark added successfully.',
                ], 200);
            }

        } catch (\Exception $err) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => $err->getMessage()
            ]);
        }
    }

    public function getBookmarks()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $bookmarks = Bookmark::with('story')->where('user_id', $user->id)->get();

            if (!$bookmarks) {
                return response()->json([
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'Bookmarks not found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Bookmarks retrieved successfully',
                'data' => $bookmarks
            ], 200);

        } catch (\Exception $err) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => $err->getMessage()
            ]);
        }
    }
}
