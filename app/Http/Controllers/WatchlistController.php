<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Watchlist;

class WatchlistController extends Controller
{
    public function store(Request $request)
    {
        // Validate the incoming data
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'items' => 'required|array'
        ]);

        $user = auth()->user();
        // Create a new watchlist entry
        $watchlist = new Watchlist();
        $watchlist->title = $validated['title'];
        $watchlist->created_by = $user->id;
        $watchlist->items = json_encode($validated['items']); // Store items with order as JSON
        $watchlist->save();

        // Return a success response
        return response()->json(['message' => 'Watchlist saved successfully'], 200);
    }


    // Get all watchlists
    public function index()
    {
        $watchlists = Watchlist::all(); // Get all watchlists from the database

        // Return the watchlists as JSON
        return response()->json($watchlists);
    }

    // Get a single watchlist by ID
    public function show($id)
    {
        $watchlist = Watchlist::with('user')->find($id); // Find the watchlist by ID

        if (!$watchlist) {
            return response()->json(['message' => 'Watchlist not found'], 404);
        }

        // Decode the JSON items into an array
        $watchlist->items = json_decode($watchlist->items);

        return response()->json($watchlist);
    }
}
