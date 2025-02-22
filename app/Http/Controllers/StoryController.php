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
<<<<<<< Updated upstream
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
=======
            // フィルターのパラメータを取り出す
            $category = $request->query('category');
            $user = $request->query('user');
            $title = $request->query('title');
            $sort = $request->query('sort', 'latest');
            $page = $request->query('page', 1);
            $per_page = $request->query('per_page', 9); 

            $stories = Story::with(['category', 'user']);

            if ($category) {
                $stories = $stories->where('category_id', $category);
            }

            if ($user) {
                $stories = $stories->where('user_id', $user);
            }

            if ($title) {
                $stories = $stories->where('title', 'like', '%' . $title . '%');
            }

            switch ($sort) {
                case 'popular':
                    $stories = $stories->withCount('bookmarks')
                    ->orderBy('bookmarks_count', 'desc');
                    break;
                
                case 'latest':
                    $stories = $stories->orderBy('created_at', 'desc');
                    break;

                case 'oldest':
                    $stories = $stories->orderBy('created_at', 'asc');
                    break;
                
                case 'asc':
                    $stories = $stories->orderBy('title', 'asc');
                    break;

                case 'desc':
                    $stories = $stories->orderBy('title', 'desc');
                    break;

                default:
                    $stories = $stories->orderBy('created_at', 'desc');
                    break;
            }
            
            $stories = $stories->withCount('bookmarks')->paginate($per_page, $page);

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
                            'popularity' => $story->bookmarks_count,
                        ];
                    })->values()->all(),
                ];
            })->values()->all();

            $sortedStories = $stories->map(function ($story) {
                return [
                    'story_id' => $story->id,
                    'title' => $story->title,
                    "author_id" => $story->user_id,
                    'author' => $story->user->name ?? null, 
                    'category_id' => $story->category_id,
                    'category' => $story->category->category_name ?? null,
                    'content' => $story->content,
                    'cover' => json_decode($story->images, true)[0] ?? null,
                    'author_img' => $story->user->avatar ?? null,
                    'created_at' => $story->created_at->toIso8601String(),
                    'popularity' => $story->bookmarks_count,
                ];
            });
    
            return response()->json([
                'status' => 'success',
                'message' => 'Stories retrieved successfully.',
                'data' => $groupedStories,
                'sorted' => $sortedStories,
                'total' => $stories->total(),
                'current_page' => $stories->currentPage(),
                'per_page' => $stories->perPage(),
            ], 200);
    
        } catch (\Exception $e) {
>>>>>>> Stashed changes
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => $th->getMessage()
            ]);
        }
    }
    public function getStoryById($id, Request $request)
    {
        $page = $request->query('page', 1);
        $per_page = $request->query('per_page', 5);

        $story = Story::with(['user', 'category'])->find($id);

        if (!$story) {
            return response()->json([
                'status' => 'error',
<<<<<<< Updated upstream
                'code' => 404,
=======
                'message' => 'Record not found',
            ], 404);
        }

        $story->images = json_decode($story->images, true);

        $similar = Story::with(['user', 'category'])
            ->where('id', '!=', $story->id)
            ->where('category_id', $story->category_id)
            ->orderBy('created_at', 'desc')
            ->paginate($per_page, ['*'], 'page', $page); // this

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
>>>>>>> Stashed changes
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
