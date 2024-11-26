<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Favorite;
use App\Models\UserMovieStatus;
use App\Models\WatchHistory;

class MovieController extends Controller
{
    // Check if a movie is a favorite
    public function isFavorite($id)
    {
        $user = auth()->user();
        $isFavorite = Favorite::where('user_id', $user->id)->where('movie_id', $id)->exists();

        return response()->json(['isFavorite' => $isFavorite]);
    }

    // Toggle favorite status for a movie
    public function toggleFavorite($id, Request $request)
    {
        $data = $request->all();
        $user = auth()->user();
        $favorite = Favorite::where('user_id', $user->id)->where('movie_id', $id)->first();

        if ($favorite) {
            $favorite->delete();
            return response()->json(['status' => 'removed']);
        } else {
            
            // Create or update the favorite
            $favorite = new Favorite();
            $favorite->user_id = $user->id;
            $favorite->movie_id = $id;
            $favorite->movie_details = json_encode($data); // Store items with order as JSON
            $favorite->save();

            return response()->json(['status' => 'added']);
        }
    }


    //Get the favorite movies for user
    public function getFavorites()
    {
        $user = auth()->user();
        $favorites = Favorite::where('user_id', $user->id)->get();

        return response()->json($favorites->map(function ($favorite) {
            return [
                'movie_id' => $favorite->movie_id,
                'movie_details' => json_decode($favorite->movie_details), // Decode JSON for easier frontend handling
            ];
        }));
    }

    // Fetch status for a movie
    public function getStatus($id)
    {
        $user = auth()->user();
        $userStatus = UserMovieStatus::where('user_id', $user->id)
            ->where('movie_id', $id)
            ->first();

        return response()->json([
            'status' => $userStatus ? $userStatus->status : 'To Be Done' // Default status if none exists
        ]);
    }

    // Update status for a movie
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:To Be Done,In Progress,Watched',
        ]);

        $user = auth()->user();
        $userStatus = UserMovieStatus::updateOrCreate(
            ['user_id' => $user->id, 'movie_id' => $id],
            ['status' => $request->status]
        );

        return response()->json(['status' => $userStatus->status]);
    }

    public function getUserFavorites()
    {
        
    }


    public function storeWatchHistory(Request $request, $id)
    {
        $user = auth()->user();

        // Check if this movie is already in watch history
        $existingEntry = WatchHistory::where('user_id', $user->id)
            ->where('movie_id', $id)
            ->first();
        $genreNames = collect($request->genres)->pluck('id')->join(', ');
        if (!$existingEntry) {
            WatchHistory::create([
                'user_id' => $user->id,
                'movie_id' => $id,
                'category' =>  $genreNames
            ]);
        }

        $watchHistoriesToDelete = WatchHistory::where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->skip(5)
            ->take(PHP_INT_MAX) // Take all remaining records after skipping the latest 10
            ->pluck('id');       // Get the IDs of the records to delete

        // Check if there are any IDs to delete
        if ($watchHistoriesToDelete->isNotEmpty()) {
            WatchHistory::whereIn('id', $watchHistoriesToDelete)->delete();
        } else {
            // Log if no records are found for deletion
            \Log::info('No old watch history records found for deletion for user ID: ' . $user->id);
        }

        return response()->json(['status' => 'added to watch history']);
    }
}
