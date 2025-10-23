<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class MovieCastController extends Controller
{
    // Get cast list normalized from movie_people/people; fallback to Movie.cast array
    public function index($movieId)
    {
        try {
            $movie = Movie::findOrFail($movieId);

            $cast = DB::table('movie_people')
                ->join('people', 'movie_people.person_id', '=', 'people.id')
                ->where('movie_people.movie_id', $movieId)
                ->where('movie_people.role', 'actor')
                ->orderBy('movie_people.billing_order')
                ->select('people.id', 'people.name', 'people.avatar', 'movie_people.character_name', 'movie_people.billing_order')
                ->get();

            if ($cast->isEmpty()) {
                // fallback: legacy array in movies.cast
                $legacy = is_array($movie->cast) ? $movie->cast : [];
                $cast = collect($legacy)->map(function ($name, $idx) {
                    return [
                        'id' => null,
                        'name' => $name,
                        'avatar' => null,
                        'character_name' => null,
                        'billing_order' => $idx + 1,
                    ];
                });
            }

            return response()->json([
                'success' => true,
                'data' => $cast,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    // Update cast for a movie
    public function update(Request $request, $movieId)
    {
        $movie = Movie::findOrFail($movieId);
        
        $validator = Validator::make($request->all(), [
            'cast' => 'array',
            'cast.*' => 'string|max:255',
            'director' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            Log::info("Starting cast update for movie {$movie->id}");
            Log::info("Request data: " . json_encode($request->all()));
            
            // Handle cast - create new actors if they don't exist
            if ($request->has('cast') && is_array($request->cast)) {
                Log::info("Processing cast array with " . count($request->cast) . " actors");
                
                // Detach existing actors first to avoid duplicates
                $detachedCount = $movie->movieCasts()->where('role', 'actor')->detach();
                Log::info("Detached {$detachedCount} existing actors for movie {$movie->id}");
                
                $actorsToAttach = [];
                foreach ($request->cast as $index => $actorName) {
                    if (empty($actorName)) continue;
                    
                    $person = Person::firstOrCreate(
                        ['name' => $actorName],
                        ['name' => $actorName]
                    );
                    
                    $actorsToAttach[$person->id] = [
                        'character_name' => null,
                        'billing_order' => $index + 1,
                        'role' => 'actor'
                    ];
                    
                    Log::info("Prepared actor: {$person->name} (ID: {$person->id}) for movie {$movie->id}");
                }
                
                if (!empty($actorsToAttach)) {
                    Log::info("Inserting " . count($actorsToAttach) . " actors to movie {$movie->id}");
                    
                    // Delete existing actors first
                    DB::table('movie_people')
                        ->where('movie_id', $movie->id)
                        ->where('role', 'actor')
                        ->delete();
                    
                    // Insert new actors
                    $insertData = [];
                    foreach ($actorsToAttach as $personId => $pivotData) {
                        $insertData[] = [
                            'movie_id' => $movie->id,
                            'person_id' => $personId,
                            'role' => 'actor',
                            'character_name' => $pivotData['character_name'],
                            'billing_order' => $pivotData['billing_order'],
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    }
                    
                    if (!empty($insertData)) {
                        $result = DB::table('movie_people')->insert($insertData);
                        Log::info("Inserted " . count($insertData) . " actors, result: " . ($result ? 'SUCCESS' : 'FAILED'));
                        
                        // Verify insertion
                        $verifyCount = DB::table('movie_people')
                            ->where('movie_id', $movie->id)
                            ->where('role', 'actor')
                            ->count();
                        Log::info("Verification: Found {$verifyCount} actors in database after insertion");
                    }
                }
            } else {
                Log::info("No cast array provided or cast is not an array");
            }

            // Handle director - create if doesn't exist
            if ($request->has('director') && !empty($request->director)) {
                Log::info("Processing director: {$request->director}");
                
                // Delete existing directors first
                $deletedCount = DB::table('movie_people')
                    ->where('movie_id', $movie->id)
                    ->where('role', 'director')
                    ->delete();
                Log::info("Deleted {$deletedCount} existing directors from database");
                
                $director = Person::firstOrCreate(
                    ['name' => $request->director],
                    ['name' => $request->director]
                );
                
                Log::info("Inserting director: {$director->name} (ID: {$director->id}) to movie {$movie->id}");
                
                // Insert director directly
                DB::table('movie_people')->insert([
                    'movie_id' => $movie->id,
                    'person_id' => $director->id,
                    'role' => 'director',
                    'character_name' => null,
                    'billing_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                Log::info("Director inserted successfully");
            } else {
                Log::info("No director provided");
            }
            
            Log::info("Cast update completed for movie {$movie->id}");

            return response()->json([
                'success' => true,
                'message' => 'Cast updated successfully',
                'data' => $movie->load(['movieCasts']),
                'debug' => [
                    'has_cast' => $request->has('cast'),
                    'cast_is_array' => is_array($request->cast),
                    'cast_count' => is_array($request->cast) ? count($request->cast) : 0,
                    'cast_data' => $request->cast,
                    'has_director' => $request->has('director'),
                    'director_data' => $request->director,
                    'actors_to_attach_count' => isset($actorsToAttach) ? count($actorsToAttach) : 0,
                    'actors_to_attach' => $actorsToAttach ?? [],
                    'database_check' => DB::table('movie_people')->where('movie_id', 15)->get()->toArray()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating cast: ' . $e->getMessage()
            ], 500);
        }
    }

    // Test method to debug insert issue
    public function testInsert($movieId)
    {
        $movie = Movie::findOrFail($movieId);
        
        $insertData = [
            [
                'movie_id' => $movie->id,
                'person_id' => 77,
                'role' => 'actor',
                'character_name' => null,
                'billing_order' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];
        
        try {
            $result = DB::table('movie_people')->insert($insertData);
            return response()->json([
                'success' => true,
                'message' => 'Test insert result: ' . ($result ? 'SUCCESS' : 'FAILED'),
                'data' => DB::table('movie_people')->where('movie_id', $movieId)->get()->toArray()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Test insert error: ' . $e->getMessage()
            ]);
        }
    }
}