<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class RecommendationController extends Controller
{
    public function getRecommendations($userId,$type,$category, Request $request)
    {
        $limit = $request->query('limit', 10); // Default limit is 10

        try {
            // Fetch user preferences and watch history from the database
            $userPrefs = DB::table('watch_histories')
                ->where('user_id', $userId)
                ->get(['category']);

            if ($userPrefs->isEmpty()) {
                return response()->json(['message' => 'No user preferences found.'], 404);
            }

            // Step 1: Extract the genre IDs from the last 3 entries
            $lastThreeGenres = $userPrefs->pluck('category')   // Get the "category" column
            ->take(-3)            // Take the last 3 entries
            ->flatMap(function ($item) {
                return explode(',', $item); // Split each "category" entry into individual genre IDs
            })
            ->unique()             // Remove duplicates within the last 3 entries
            ->take(3);             // Take only 3 genre from these
            //sample 28, 18, 80, 53, 878,27, 9648
            // Step 2: Get the top 4 most frequent genre IDs from the entire dataset
            $topFourGenres = $userPrefs->pluck('category')
            ->flatMap(function ($item) {
            return explode(',', $item); // Split each "category" entry into individual genre IDs
            })
            ->countBy()              // Count the frequency of each genre ID
            ->sortDesc()             // Sort in descending order by frequency
            ->keys()                 // Get the genre IDs (keys) of the most frequent items
            ->diff($lastThreeGenres) // Exclude the genre taken from the last 3 entries
            ->take(2);               // Take the top 4 genres

            // Step 3: Merge the selected genres and convert to a comma-separated string
            $genreIds = $lastThreeGenres->merge($topFourGenres)
            ->implode(','); 

    
            // Fetch recommendations from TMDB API based on genres and actors
            $endpoint = 'https://api.themoviedb.org/3/discover';
            if($category){
                $endpoint = $endpoint."/".$category;
            }
            $sort_by = 'popularity.desc'; // Default sort


            if($category == 'movie'){
                if ($type === 'popular') {
                    $sort_by = 'popularity.desc';
                } elseif ($type === 'top_rated') {
                    $sort_by = 'vote_average.desc';
                } elseif ($type === 'upcoming') {
                    $sort_by = 'release_date.desc';
                }
            }else{
                // popular: 'popular',
                // top_rated: 'top_rated',
                // on_the_air: 'on_the_air'
                if ($type === 'popular') {
                    $sort_by = 'popularity.desc';
                } elseif ($type === 'top_rated') {
                    $sort_by = 'vote_average.desc';
                } elseif ($type === 'on_the_air') {
                    $sort_by = 'first_air_date.desc';
                }
            }


            $response = Http::get($endpoint, [
                'api_key' => env('TMDB_API_KEY'),
                'with_genres' => $genreIds,
                'sort_by' => $sort_by,
                'page' => 1
            ]);
           
            if ($response->failed()) {
                return response()->json(['message' => 'Failed to fetch data from TMDB.'], 500);
            }

            $movies = $response->json()['results'];

            // Limit the number of movies and format the JSON response
            $recommendations = collect($movies)->take($limit)->map(function ($movie) use($category) {
                return [
                    'id' => $movie['id'],
                    'title' => $category == 'movie' ? $movie['title'] : $movie['name'],
                    'overview' => $movie['overview'],
                    'release_date' => $category == 'movie' ? $movie['release_date'] : $movie['first_air_date'],
                    'popularity' => $movie['popularity'],
                    'genre_ids' => $movie['genre_ids'],
                    'poster_path' => $movie['poster_path'],
                    'backdrop_path' => $movie['backdrop_path']
                ];
            });



            return response()->json([
                'page' => $response->json()['page'],
                'total_results' => $response->json()['total_results'],
                'total_pages' => $response->json()['total_pages'],
                'results' => $recommendations
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Error fetching recommendations: ' . $e->getMessage()], 500);
        }
    }
}


