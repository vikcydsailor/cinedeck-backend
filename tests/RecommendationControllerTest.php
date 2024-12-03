<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Http\Controllers\RecommendationController;

class RecommendationControllerTest extends TestCase
{
    /**
     * Test getRecommendations method.
     */
    public function testGetRecommendations()
    {
        // Mock data for the database
        $mockWatchHistory = collect([
            (object)['category' => '28,18,80'],
            (object)['category' => '53,878,27'],
            (object)['category' => '9648,18,28'],
        ]);

        // Mock DB interaction
        DB::shouldReceive('table')
            ->with('watch_histories')
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->with('user_id', 1)
            ->andReturnSelf();
        DB::shouldReceive('get')
            ->andReturn($mockWatchHistory);

        // Mock HTTP response
        $mockApiResponse = [
            'page' => 1,
            'total_results' => 50,
            'total_pages' => 5,
            'results' => [
                [
                    'id' => 1,
                    'title' => 'Example Movie',
                    'overview' => 'An example overview.',
                    'release_date' => '2024-12-01',
                    'popularity' => 8.7,
                    'genre_ids' => [28, 18],
                    'poster_path' => '/example_poster.jpg',
                    'backdrop_path' => '/example_backdrop.jpg',
                ]
            ],
        ];

        Http::fake([
            'https://api.themoviedb.org/3/discover/movie' => Http::response($mockApiResponse, 200),
        ]);

        // Create a mock request with query parameters
        $request = new Request(['limit' => 5]);

        // Instantiate the controller and call the method
        $controller = new RecommendationController();
        $response = $controller->getRecommendations(1, 'popular', 'movie', $request);

        // Assert the response
        $response->assertStatus(200);
        $responseData = $response->getData(true);

        $this->assertEquals(1, $responseData['page']);
        $this->assertEquals(50, $responseData['total_results']);
        $this->assertCount(1, $responseData['results']);
        $this->assertEquals('Example Movie', $responseData['results'][0]['title']);
    }

    /**
     * Test getRecommendations with no preferences.
     */
    public function testGetRecommendationsNoPreferences()
    {
        // Mock empty watch history
        DB::shouldReceive('table')
            ->with('watch_histories')
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->with('user_id', 2)
            ->andReturnSelf();
        DB::shouldReceive('get')
            ->andReturn(collect([]));

        // Create a mock request
        $request = new Request(['limit' => 5]);

        // Instantiate the controller and call the method
        $controller = new RecommendationController();
        $response = $controller->getRecommendations(2, 'popular', 'movie', $request);

        // Assert the response
        $response->assertStatus(404);
        $responseData = $response->getData(true);

        $this->assertEquals('No user preferences found.', $responseData['message']);
    }

    /**
     * Test getRecommendations with TMDB API failure.
     */
    public function testGetRecommendationsApiFailure()
    {
        // Mock data for the database
        $mockWatchHistory = collect([
            (object)['category' => '28,18,80'],
            (object)['category' => '53,878,27'],
        ]);

        // Mock DB interaction
        DB::shouldReceive('table')
            ->with('watch_histories')
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->with('user_id', 3)
            ->andReturnSelf();
        DB::shouldReceive('get')
            ->andReturn($mockWatchHistory);

        // Mock failed API response
        Http::fake([
            'https://api.themoviedb.org/3/discover/movie' => Http::response([], 500),
        ]);

        // Create a mock request
        $request = new Request(['limit' => 5]);

        // Instantiate the controller and call the method
        $controller = new RecommendationController();
        $response = $controller->getRecommendations(3, 'popular', 'movie', $request);

        // Assert the response
        $response->assertStatus(500);
        $responseData = $response->getData(true);

        $this->assertEquals('Failed to fetch data from TMDB.', $responseData['message']);
    }
}
