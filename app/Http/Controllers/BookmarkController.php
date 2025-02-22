<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    public function addBookmark(Request $request)
    {
         
    }
<<<<<<< Updated upstream
=======

    public function getBookmarks(Request $request)
    {
        try {
            $page = $request->query('page', 1);
            $per_page = $request->query('per_page', 4);

            $user = JWTAuth::parseToken()->authenticate();
            $bookmarks = Bookmark::with('story', 'user')->where('user_id', $user->id)->orderBy('created_at', 'desc')->get();

            $data = $bookmarks->map(function ($bookmarks) {
                $story = $bookmarks->story;
                return [
                    'id' => $bookmarks->id,
                    'story_id' => $story->id,
                    'title' => $story->title,
                    'content' => $story->content,
                    'cover' => json_decode($story->images, true)[0] ?? null,
                    'category' => $story->category->category_name,
                    'author' => $story->user->name,
                    'author_avatar' => $story->user->avatar,
                    'created_at' => $story->created_at,
                ];
            });

            if (!$bookmarks) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Bookmarks not found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Bookmarks retrieved successfully',
                'data' => $data,
            ], 200);

        } catch (\Exception $err) {
            return response()->json([
                'status' => 'error',
                'message' => $err->getMessage()
            ], 500);
        }
    }

    // MANUAL ADD AND DELETE
    public function addBookmark($id)
    {
         try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
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
                    'message' => 'Record Already Exist',
                ], 409);
            }

            Bookmark::create([
                'user_id' => $user->id,
                'story_id' => $story->id
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Record Added successfully.',
            ], 200);

         } catch (\Exception $err) {
            return response()->json([
                'status' => 'error',
                'message' => $err->getMessage()
            ], 500);
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
                    'message' => 'You are not authorized to delete this bookmark',
                ], 403);
            }

            $bookmark->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Bookmark deleted successfully.',
            ], 200);

        } catch (\Exception $err) {
            return response()->json([
                'status' => 'error',
                'message' => $err->getMessage()
            ], 500);
        }
    }

>>>>>>> Stashed changes
}
