<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class RecommendationService
{
    private $tmdbApiKey;

    public function __construct()
    {
        $this->tmdbApiKey = env('TMDB_API_KEY');
    }

    public function getMovieRecommendations($userId)
    {
        // Fetch user's watch history
        $watchHistory = $this->fetchUserWatchHistory($userId);

        $recommendations = collect();

        // Loop through each watched movie and get recommendations
        foreach ($watchHistory as $history) {
            // Get recommendations based on movie and genre
            $movieRecommendations = $this->fetchMovieRecommendations($history->movie_id);
            $genreRecommendations = $this->fetchGenreRecommendations($history->category);

            // Merge and filter unique recommendations
            $recommendations = $recommendations
                ->merge($movieRecommendations)
                ->merge($genreRecommendations)
                ->unique('id');
        }

        return $recommendations;
    }

    private function fetchUserWatchHistory($userId)
    {
        return \DB::table('watch_histories')->where('user_id', $userId)->get();
    }

    private function fetchMovieRecommendations($movieId)
    {
        $response = Http::get("https://api.themoviedb.org/3/movie/{$movieId}/recommendations", [
            'api_key' => $this->tmdbApiKey
        ]);

        return $response->json()['results'] ?? [];
    }

    private function fetchGenreRecommendations($genreId)
    {
        $response = Http::get("https://api.themoviedb.org/3/discover/movie", [
            'api_key' => $this->tmdbApiKey,
            'with_genres' => $genreId
        ]);

        return $response->json()['results'] ?? [];
    }
}

