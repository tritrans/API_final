<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use App\Models\Genre;
use App\Models\Schedule;
use App\Services\GoogleDriveService;
use App\Services\ScheduleGenerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Traits\ApiResponse;
use App\Enums\ErrorCode;
use OpenApi\Attributes as OA;

class MovieController extends Controller
{
    use ApiResponse;

    #[OA\Get(
        path: "/api/movies",
        summary: "Get all movies",
        description: "Get paginated list of movies with optional filters",
        tags: ["Movies"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "genre",
                in: "query",
                description: "Filter by genre",
                required: false,
                schema: new OA\Schema(type: "string", example: "Action")
            ),
            new OA\Parameter(
                name: "featured",
                in: "query",
                description: "Filter featured movies",
                required: false,
                schema: new OA\Schema(type: "boolean", example: true)
            ),
            new OA\Parameter(
                name: "page",
                in: "query",
                description: "Page number",
                required: false,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Movies retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Movies retrieved successfully"),
                        new OA\Property(
                            property: "data", 
                            type: "object",
                            properties: [
                                new OA\Property(
                                    property: "movies", 
                                    type: "array", 
                                    items: new OA\Items(ref: "#/components/schemas/Movie")
                                ),
                                new OA\Property(property: "total", type: "integer", example: 50),
                                new OA\Property(property: "current_page", type: "integer", example: 1),
                                new OA\Property(property: "per_page", type: "integer", example: 15),
                                new OA\Property(property: "last_page", type: "integer", example: 4)
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    public function index(Request $request)
    {
        // Public access - no authentication required for viewing movies
        // Permission check only for authenticated users
        if (auth()->check() && !auth()->user()->can('view movies')) {
            return $this->errorResponse(ErrorCode::UNAUTHORIZED, null, 'Insufficient permissions to view movies');
        }

        $query = Movie::with(['genres', 'movieCasts'])
            ->withCount('reviews');

        // Filter by genre
        if ($request->has('genre')) {
            $genre = $request->genre;
            $query->whereHas('genres', function($q) use ($genre) {
                $q->where('name', $genre);
            });
        }

        // Filter by featured
        if ($request->has('featured')) {
            $query->featured();
        }

        // Search
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $movies = $query->paginate($request->get('per_page', 12));
        
        // Append genre field and calculate rating for each movie
        $movies->getCollection()->transform(function ($movie) {
            $movie = $movie->append('genre');
            // Calculate rating from reviews
            $avgRating = $movie->reviews()->avg('rating');
            $movie->rating = $avgRating ? round($avgRating, 1) : null;
            return $movie;
        });

        return $this->successResponse($movies, 'Movies retrieved successfully');
    }

    /**
     * Display featured movies
     */
    public function featured()
    {
        $movies = Movie::featured()->with(['genres', 'movieCasts'])->latest()->take(10)->get();
        
        // Append genre field and calculate rating for each movie
        $movies->transform(function ($movie) {
            $movie = $movie->append('genre');
            // Calculate rating from reviews
            $avgRating = $movie->reviews()->avg('rating');
            $movie->rating = $avgRating ? round($avgRating, 1) : null;
            return $movie;
        });

        return response()->json([
            'success' => true,
            'data' => $movies
        ]);
    }

    /**
     * Search movies
     */
    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:2'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $movies = Movie::search($request->q)->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $movies
        ]);
    }

    /**
     * Display the specified movie
     */
    public function show($id)
    {
        $movie = Movie::with(['reviews.user', 'comments.user', 'movieCasts'])->findOrFail($id);
        
        // Append genre field
        $movie->append('genre');
        
        // Format cast array for frontend
        $movie->cast = $movie->movieCasts->where('pivot.role', 'actor')->pluck('name')->toArray();
        $movie->director = $movie->movieCasts->where('pivot.role', 'director')->pluck('name')->first() ?? 'Chưa cập nhật';

        return response()->json([
            'success' => true,
            'data' => $movie
        ]);
    }

    /**
     * Store a newly created movie
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'title_vi' => 'nullable|string|max:255',
            'description' => 'required|string',
            'description_vi' => 'nullable|string',
            'poster' => 'required|url',
            'backdrop' => 'nullable|url',
            'trailer' => 'nullable|url',
            'release_date' => 'required|date',
            'duration' => 'required|integer|min:1',
            'genre' => 'required|array',
            'country' => 'required|string|max:255',
            'language' => 'required|string|max:255',
            'director' => 'required|string|max:255',
            'cast' => 'required|array',
            'featured' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        $data['slug'] = Str::slug($request->title);
        $data['rating'] = 0;

        $movie = Movie::create($data);

        // Handle genres - create new ones if they don't exist
        if ($request->has('genre')) {
            $genreIds = [];
            foreach ($request->genre as $genreName) {
                $genre = Genre::firstOrCreate(
                    ['name' => $genreName],
                    [
                        'name' => $genreName,
                        'name_vi' => $genreName, // Default to same name
                        'slug' => Str::slug($genreName),
                        'description' => $genreName . ' movies'
                    ]
                );
                $genreIds[] = $genre->id;
            }
            $movie->genres()->attach($genreIds);
        }

        // Handle cast - create new actors if they don't exist
        if ($request->has('cast')) {
            foreach ($request->cast as $index => $actorName) {
                $person = \App\Models\Person::firstOrCreate(
                    ['name' => $actorName],
                    ['name' => $actorName]
                );
                
                $movie->movieCasts()->attach($person->id, [
                    'character_name' => null,
                    'billing_order' => $index + 1,
                    'role' => 'actor'
                ]);
            }
        }

        // Handle director - create if doesn't exist
        if ($request->has('director')) {
            $director = \App\Models\Person::firstOrCreate(
                ['name' => $request->director],
                ['name' => $request->director]
            );
            
            $movie->movieCasts()->attach($director->id, [
                'character_name' => null,
                'billing_order' => 0,
                'role' => 'director'
            ]);
        }

        // Auto-generate schedules for the movie
        $scheduleService = new ScheduleGenerationService();
        $schedules = $scheduleService->generateSchedulesForMovie($movie);return response()->json([
            'success' => true,
            'message' => 'Movie created successfully',
            'data' => $movie->load(['genres'])
        ], 201);
    }

    /**
     * Update the specified movie
     */
    public function update(Request $request, $id)
    {
        error_log('Movie update method called for ID: ' . $id);
        error_log('Request data: ' . json_encode($request->all()));
        error_log('Memory usage: ' . memory_get_usage(true) / 1024 / 1024 . ' MB');
        error_log('Time limit: ' . ini_get('max_execution_time'));
        
        $movie = Movie::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'title_vi' => 'nullable|string|max:255',
            'description' => 'sometimes|required|string',
            'description_vi' => 'nullable|string',
            'poster' => 'sometimes|required|url',
            'backdrop' => 'nullable|url',
            'trailer' => 'nullable|url',
            'release_date' => 'sometimes|required|date',
            'duration' => 'sometimes|required|integer|min:1',
            'genre' => 'sometimes|required|array',
            'country' => 'sometimes|required|string|max:255',
            'language' => 'sometimes|required|string|max:255',
            'director' => 'sometimes|required|string|max:255',
            'cast' => 'sometimes|required|array',
            'featured' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        if ($request->has('title')) {
            $data['slug'] = Str::slug($request->title);
        }

        // Store original release date before update
        $originalReleaseDate = $movie->release_date;
        
        $movie->update($data);

        // Handle genres - create new ones if they don't exist
        if ($request->has('genre')) {
            // Detach existing genres
            $movie->genres()->detach();
            
            $genreIds = [];
            foreach ($request->genre as $genreName) {
                $genre = Genre::firstOrCreate(
                    ['name' => $genreName],
                    [
                        'name' => $genreName,
                        'name_vi' => $genreName, // Default to same name
                        'slug' => Str::slug($genreName),
                        'description' => $genreName . ' movies'
                    ]
                );
                $genreIds[] = $genre->id;
            }
            $movie->genres()->attach($genreIds);
        }

        // Debug log incoming request
        error_log('MovieController: Received request data: ' . json_encode($request->all()));
        error_log('MovieController: Has cast field: ' . ($request->has('cast') ? 'Yes' : 'No'));
        error_log('MovieController: Cast data: ' . json_encode($request->cast));
        
        // Handle cast and director in a single transaction
        if ($request->has('cast') || $request->has('director')) {
            \DB::transaction(function () use ($movie, $request) {
                // Handle cast - create new actors if they don't exist
                if ($request->has('cast')) {
                    // Detach existing actors
                    $detachedCount = $movie->movieCasts()->where('role', 'actor')->detach();
                    error_log("Detached {$detachedCount} existing actors for movie {$movie->id}");
                    
                    // Add debug log
                    error_log('MovieController: Processing cast - movie_id: ' . $movie->id . ', cast_count: ' . count($request->cast) . ', cast_data: ' . json_encode($request->cast));
                    
                    // Insert actors directly to database
                    $insertData = [];
                    foreach ($request->cast as $index => $actorName) {
                        $person = \App\Models\Person::firstOrCreate(
                            ['name' => $actorName],
                            ['name' => $actorName]
                        );
                        
                        error_log('MovieController: Processing actor - person_id: ' . $person->id . ', person_name: ' . $person->name . ', billing_order: ' . ($index + 1));
                        
                        $insertData[] = [
                            'movie_id' => $movie->id,
                            'person_id' => $person->id,
                            'character_name' => null,
                            'billing_order' => $index + 1,
                            'role' => 'actor',
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    }
                    
                    if (!empty($insertData)) {
                        $result = \DB::table('movie_people')->insert($insertData);
                        error_log('MovieController: Inserted ' . count($insertData) . ' actors, result: ' . ($result ? 'SUCCESS' : 'FAILED'));
                    }
                }

                // Handle director - create if doesn't exist
                if ($request->has('director')) {
                    // Delete existing director
                    \DB::table('movie_people')
                        ->where('movie_id', $movie->id)
                        ->where('role', 'director')
                        ->delete();
                    
                    $director = \App\Models\Person::firstOrCreate(
                        ['name' => $request->director],
                        ['name' => $request->director]
                    );
                    
                    error_log('MovieController: Processing director - person_id: ' . $director->id . ', person_name: ' . $director->name);
                    
                    // Insert director directly
                    $result = \DB::table('movie_people')->insert([
                        'movie_id' => $movie->id,
                        'person_id' => $director->id,
                        'character_name' => null,
                        'billing_order' => 0,
                        'role' => 'director',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    
                    error_log('MovieController: Inserted director, result: ' . ($result ? 'SUCCESS' : 'FAILED'));
                }
            });
        }

        // Auto-generate schedules if release date was actually changed
        error_log('Checking release date logic...');
        if ($request->has('release_date')) {
            $newReleaseDate = $request->release_date;
            error_log('Release date in request: ' . $newReleaseDate);
            error_log('Original release date: ' . $originalReleaseDate);
            
            // Convert both dates to same format for comparison
            $originalDateFormatted = \Carbon\Carbon::parse($originalReleaseDate)->format('Y-m-d');
            $newDateFormatted = \Carbon\Carbon::parse($newReleaseDate)->format('Y-m-d');
            
            error_log('Original formatted: ' . $originalDateFormatted);
            error_log('New formatted: ' . $newDateFormatted);
            
            // Only regenerate if the date actually changed
            if ($originalDateFormatted !== $newDateFormatted) {
                error_log('Release date changed from ' . $originalDateFormatted . ' to ' . $newDateFormatted . ' for movie: ' . $movie->id);
                try {
                    $scheduleService = new ScheduleGenerationService();
                    $schedules = $scheduleService->regenerateSchedulesForMovie($movie);
                    error_log('Regenerated ' . count($schedules) . ' schedules for movie: ' . $movie->title);
                } catch (\Exception $e) {
                    error_log('Error regenerating schedules: ' . $e->getMessage());
                }
            } else {
                error_log('Release date unchanged for movie: ' . $movie->id . ' (old: ' . $originalDateFormatted . ', new: ' . $newDateFormatted . ')');
            }
        } else {
            error_log('No release date field in request for movie: ' . $movie->id);
        }
        
        error_log('About to return response...');

        try {
            error_log('Returning response without loading relationships...');
            return response()->json([
                'success' => true,
                'message' => 'Movie updated successfully',
                'data' => [
                    'id' => $movie->id,
                    'title' => $movie->title,
                    'slug' => $movie->slug,
                    'updated_at' => $movie->updated_at
                ]
            ]);
        } catch (\Exception $e) {
            error_log('Error returning response: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating movie: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create movie with file uploads
     */
    public function storeWithFiles(Request $request, GoogleDriveService $googleDriveService)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'title_vi' => 'nullable|string|max:255',
            'description' => 'required|string',
            'description_vi' => 'nullable|string',
            'poster' => 'required', // Can be file or URL
            'backdrop' => 'nullable', // Can be file or URL
            'trailer' => 'nullable', // Can be file or URL
            'release_date' => 'required|date',
            'duration' => 'required|integer|min:1',
            'genre' => 'required|array',
            'country' => 'required|string|max:255',
            'language' => 'required|string|max:255',
            'director' => 'required|string|max:255',
            'cast' => 'required|array',
            'featured' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        try {
            // Handle poster - can be file or URL
            $posterUrl = null;
            if ($request->hasFile('poster')) {
                $posterUrl = $googleDriveService->uploadFile($request->file('poster'), 'poster');
                if (!$posterUrl) {
                    return $this->errorResponse(ErrorCode::FILE_UPLOAD_ERROR, null, 'Failed to upload poster');
                }
            } elseif ($request->filled('poster')) {
                $posterUrl = $request->input('poster'); // Assume it's already a URL
            } else {
                return $this->errorResponse(ErrorCode::VALIDATION_ERROR, null, 'Poster is required');
            }

            // Handle backdrop - can be file or URL
            $backdropUrl = null;
            if ($request->hasFile('backdrop')) {
                $backdropUrl = $googleDriveService->uploadFile($request->file('backdrop'), 'backdrop');
            } elseif ($request->filled('backdrop')) {
                $backdropUrl = $request->input('backdrop'); // Assume it's already a URL
            }

            // Handle trailer - can be file or URL
            $trailerUrl = null;
            if ($request->hasFile('trailer')) {
                $trailerUrl = $googleDriveService->uploadFile($request->file('trailer'), 'trailer');
            } elseif ($request->filled('trailer')) {
                $trailerUrl = $request->input('trailer'); // Assume it's already a URL
            }

            // Create movie data
            $data = $request->all();
            $data['slug'] = Str::slug($request->title);
            $data['rating'] = 0;
            $data['poster'] = $posterUrl;
            $data['backdrop'] = $backdropUrl;
            $data['trailer'] = $trailerUrl;

            $movie = Movie::create($data);

            // Handle genres - create new ones if they don't exist
            if ($request->has('genre')) {
                $genreIds = [];
                foreach ($request->genre as $genreName) {
                    $genre = Genre::firstOrCreate(
                        ['name' => $genreName],
                        [
                            'name' => $genreName,
                            'name_vi' => $genreName, // Default to same name
                            'slug' => Str::slug($genreName),
                            'description' => $genreName . ' movies'
                        ]
                    );
                    $genreIds[] = $genre->id;
                }
                $movie->genres()->attach($genreIds);
            }

            // Handle cast - create new actors if they don't exist
            if ($request->has('cast')) {
                foreach ($request->cast as $index => $actorName) {
                    $person = \App\Models\Person::firstOrCreate(
                        ['name' => $actorName],
                        ['name' => $actorName]
                    );
                    
                    $movie->movieCasts()->attach($person->id, [
                        'character_name' => null,
                        'billing_order' => $index + 1,
                        'role' => 'actor'
                    ]);
                }
            }

            // Handle director - create if doesn't exist
            if ($request->has('director')) {
                $director = \App\Models\Person::firstOrCreate(
                    ['name' => $request->director],
                    ['name' => $request->director]
                );
                
                $movie->movieCasts()->attach($director->id, [
                    'character_name' => null,
                    'billing_order' => 0,
                    'role' => 'director'
                ]);
            }

            return $this->createdResponse($movie->load('genres'), 'Movie created successfully with files');

        } catch (\Exception $e) {
            return $this->errorResponse(ErrorCode::FILE_UPLOAD_ERROR, null, 'Failed to create movie: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified movie
     */
    public function destroy($id)
    {
        $movie = Movie::findOrFail($id);
        $movie->delete();

        return response()->json([
            'success' => true,
            'message' => 'Movie deleted successfully'
        ]);
    }

    /**
     * Generate schedules for a movie (Admin endpoint)
     */
    public function generateSchedules(Request $request, $id)
    {
        $movie = Movie::findOrFail($id);
        
        try {
            $scheduleService = new ScheduleGenerationService();
            $schedules = $scheduleService->generateSchedulesForMovie($movie);
            
            return response()->json([
                'success' => true,
                'message' => 'Schedules generated successfully',
                'data' => [
                    'movie_id' => $movie->id,
                    'movie_title' => $movie->title,
                    'schedules_generated' => count($schedules),
                    'schedules' => $schedules
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate schedules: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Regenerate schedules for a movie (Admin endpoint)
     */
    public function regenerateSchedules(Request $request, $id)
    {
        $movie = Movie::findOrFail($id);
        
        try {
            $scheduleService = new ScheduleGenerationService();
            $schedules = $scheduleService->regenerateSchedulesForMovie($movie);
            
            return response()->json([
                'success' => true,
                'message' => 'Schedules regenerated successfully',
                'data' => [
                    'movie_id' => $movie->id,
                    'movie_title' => $movie->title,
                    'schedules_generated' => count($schedules),
                    'schedules' => $schedules
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to regenerate schedules: ' . $e->getMessage()
            ], 500);
        }
    }
}
