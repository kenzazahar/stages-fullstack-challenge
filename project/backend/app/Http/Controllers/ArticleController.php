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
    //  CORRECTION N+1 : Cache de 1 minute sauf si mode test activé
    if ($request->has('performance_test')) {
        // Mode test : pas de cache pour voir le vrai problème N+1
        $cacheKey = 'articles_test_' . time();
        $cacheDuration = 1; // 1 seconde
    } else {
        $cacheKey = 'articles_list';
        $cacheDuration = 60; // 1 minute
    }

    //  CORRECTION : Cache les données, pas la Response
    $articles = Cache::remember($cacheKey, $cacheDuration, function () use ($request) {
        //  CORRECTION N+1 : Eager loading de l'auteur et count des commentaires
        // Au lieu de charger l'auteur et les commentaires pour chaque article (N+1),
        // on charge tout en 3 requêtes : articles, authors, comments_count
        $articles = Article::with('author')
            ->withCount('comments')
            ->orderBy('published_at', 'desc')
            ->get();

        $articles = $articles->map(function ($article) use ($request) {
            //  Option 1 : Supprimer complètement le usleep (recommandé)
            // Le problème N+1 est résolu, pas besoin de simuler
            
            //  Option 2 : Réduire le délai (si vous voulez garder une simulation)
            if ($request->has('performance_test')) {
                // Réduire à 1ms par article pour montrer que c'est rapide maintenant
                usleep(1000); // 1ms au lieu de 30ms
            }

            return [
                'id' => $article->id,
                'title' => $article->title,
                'content' => substr($article->content, 0, 200) . '...',
                'author' => $article->author->name, //  Déjà chargé via with('author')
                'comments_count' => $article->comments_count, //  Déjà calculé via withCount('comments')
                'published_at' => $article->published_at,
                'created_at' => $article->created_at,
            ];
        });

        //  Retourner les données (array), pas une Response
        return $articles->toArray();
    });

    //  Créer la Response en dehors du cache
    return response()->json($articles);
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

