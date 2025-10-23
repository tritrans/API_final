<?php

namespace App\Http\Requests;

use App\Enums\ErrorCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

abstract class BaseApiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    abstract public function rules(): array;

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'required' => 'The :attribute field is required.',
            'email' => 'The :attribute must be a valid email address.',
            'unique' => 'The :attribute has already been taken.',
            'min' => [
                'string' => 'The :attribute must be at least :min characters.',
                'numeric' => 'The :attribute must be at least :min.',
            ],
            'max' => [
                'string' => 'The :attribute may not be greater than :max characters.',
                'numeric' => 'The :attribute may not be greater than :max.',
            ],
            'confirmed' => 'The :attribute confirmation does not match.',
            'exists' => 'The selected :attribute is invalid.',
            'date' => 'The :attribute is not a valid date.',
            'numeric' => 'The :attribute must be a number.',
            'string' => 'The :attribute must be a string.',
            'boolean' => 'The :attribute field must be true or false.',
            'array' => 'The :attribute must be an array.',
            'json' => 'The :attribute must be a valid JSON string.',
            'url' => 'The :attribute format is invalid.',
            'image' => 'The :attribute must be an image.',
            'file' => 'The :attribute must be a file.',
            'between' => [
                'numeric' => 'The :attribute must be between :min and :max.',
                'string' => 'The :attribute must be between :min and :max characters.',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'name',
            'email' => 'email',
            'password' => 'password',
            'password_confirmation' => 'password confirmation',
            'title' => 'title',
            'description' => 'description',
            'poster' => 'poster',
            'trailer' => 'trailer',
            'release_date' => 'release date',
            'duration' => 'duration',
            'genre' => 'genre',
            'rating' => 'rating',
            'country' => 'country',
            'language' => 'language',
            'director' => 'director',
            'cast' => 'cast',
            'seats' => 'seats',
            'total_amount' => 'total amount',
            'date_time' => 'date time',
            'theater' => 'theater',
            'comment' => 'comment',
            'content' => 'content',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'error_code' => ErrorCode::VALIDATION_ERROR['code'],
                'message' => ErrorCode::VALIDATION_ERROR['message'],
                'status_code' => ErrorCode::VALIDATION_ERROR['status'],
                'errors' => $validator->errors()
            ], ErrorCode::VALIDATION_ERROR['status'])
        );
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'error_code' => ErrorCode::UNAUTHORIZED['code'],
                'message' => ErrorCode::UNAUTHORIZED['message'],
                'status_code' => ErrorCode::UNAUTHORIZED['status']
            ], ErrorCode::UNAUTHORIZED['status'])
        );
    }
}
