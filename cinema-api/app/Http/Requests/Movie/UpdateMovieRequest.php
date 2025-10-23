<?php

namespace App\Http\Requests\Movie;

use App\Http\Requests\BaseApiRequest;

class UpdateMovieRequest extends BaseApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'title_vi' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'description_vi' => 'sometimes|required|string',
            'poster' => 'sometimes|required|string|url',
            'backdrop' => 'nullable|string|url',
            'trailer' => 'nullable|string|url',
            'release_date' => 'sometimes|required|date',
            'duration' => 'sometimes|required|integer|min:1',
            'genre' => 'sometimes|required|array',
            'genre.*' => 'string',
            'rating' => 'nullable|numeric|between:0,10',
            'country' => 'sometimes|required|string|max:100',
            'language' => 'sometimes|required|string|max:100',
            'director' => 'sometimes|required|string|max:255',
            'cast' => 'sometimes|required|array',
            'cast.*' => 'string',
            'featured' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'title.required' => 'Movie title is required.',
            'title_vi.required' => 'Vietnamese title is required.',
            'description.required' => 'Movie description is required.',
            'description_vi.required' => 'Vietnamese description is required.',
            'poster.required' => 'Movie poster URL is required.',
            'poster.url' => 'Poster must be a valid URL.',
            'release_date.required' => 'Release date is required.',
            'release_date.date' => 'Release date must be a valid date.',
            'duration.required' => 'Movie duration is required.',
            'duration.integer' => 'Duration must be a number.',
            'duration.min' => 'Duration must be at least 1 minute.',
            'genre.required' => 'Movie genre is required.',
            'genre.array' => 'Genre must be an array.',
            'rating.between' => 'Rating must be between 0 and 10.',
            'country.required' => 'Country is required.',
            'language.required' => 'Language is required.',
            'director.required' => 'Director is required.',
            'cast.required' => 'Cast information is required.',
            'cast.array' => 'Cast must be an array.',
        ]);
    }
}
