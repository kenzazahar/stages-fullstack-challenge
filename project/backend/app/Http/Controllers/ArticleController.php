<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ArticleController extends Controller
{
    /**
     * Display a listing of articles.
     */
    public function index(Request $request)
{
    //  CORRECTION : Cache de 1 minute sauf si mode test activé
    if ($request->has('performance_test')) {
        // Mode test : pas de cache pour voir le vrai problème N+1
        $cacheKey = 'articles_test_' . time();
        $cacheDuration = 1; // 1 seconde
    } else {
        $cacheKey = 'articles_list';
        $cacheDuration = 60; // 1 minute
    }

    return Cache::remember($cacheKey, $cacheDuration, function () use ($request) {
        $articles = Article::with('author')->withCount('comments')->get();

        $articles = $articles->map(function ($article) use ($request) {
            if ($request->has('performance_test')) {
                usleep(30000); // 30ms par article pour simuler le coût du N+1
            }

            return [
                'id' => $article->id,
                'title' => $article->title,
                'content' => substr($article->content, 0, 200) . '...',
                'author' => $article->author->name,
                'comments_count' => $article->comments_count,
                'published_at' => $article->published_at,
                'created_at' => $article->created_at,
            ];
        });

        return response()->json($articles);
    });
}

    /**
     * Display the specified article.
     */
    public function show($id)
    {
        $article = Article::with(['author', 'comments.user'])->findOrFail($id);

        return response()->json([
            'id' => $article->id,
            'title' => $article->title,
            'content' => $article->content,
            'author' => $article->author->name,
            'author_id' => $article->author->id,
            'image_path' => $article->image_path,
            'published_at' => $article->published_at,
            'created_at' => $article->created_at,
            'comments' => $article->comments->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'content' => $comment->content,
                    'user' => $comment->user->name,
                    'created_at' => $comment->created_at,
                ];
            }),
        ]);
    }

    /**
     * Search articles.
     */
    public function search(Request $request)
    {
        $query = $request->input('q');

        if (!$query) {
            return response()->json([]);
        }

        //  CORRECTION : Utiliser Query Builder avec paramètres liés (protection SQL injection)
    $articles = Article::where('title', 'LIKE', "%{$query}%")
        ->orWhere('content', 'LIKE', "%{$query}%")
        ->get(['id', 'title', 'content', 'published_at']);

        $results = $articles->map(function ($article) {
            return [
                'id' => $article->id,
                'title' => $article->title,
                'content' => substr($article->content, 0, 200),
                'published_at' => $article->published_at,
            ];
        })->values();

        return response()->json($results);
    }

    /**
 * Store a newly created article.
 */
public function store(Request $request)
{
    $validated = $request->validate([
        'title' => 'required|max:255',
        'content' => 'required',
        'author_id' => 'required|exists:users,id',
        'image_path' => 'nullable|string',
    ]);

    $article = Article::create([
        'title' => $validated['title'],
        'content' => $validated['content'],
        'author_id' => $validated['author_id'],
        'image_path' => $validated['image_path'] ?? null,
        'published_at' => now(),
    ]);

    //  INVALIDATION : Supprimer le cache
    Cache::forget('articles_list');
    Cache::forget('stats');

    return response()->json($article, 201);
}

/**
 * Update the specified article.
 */
public function update(Request $request, $id)
{
    $article = Article::findOrFail($id);

    $validated = $request->validate([
        'title' => 'sometimes|required|max:255',
        'content' => 'sometimes|required',
    ]);

    $article->update($validated);

    //  INVALIDATION : Supprimer le cache
    Cache::forget('articles_list');

    return response()->json($article);
}

/**
 * Remove the specified article.
 */
public function destroy($id)
{
    $article = Article::findOrFail($id);
    $article->delete();

    //  INVALIDATION : Supprimer le cache
    Cache::forget('articles_list');
    Cache::forget('stats');

    return response()->json(['message' => 'Article deleted successfully']);
}
}

