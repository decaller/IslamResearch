<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PrefectWebhookRequest extends FormRequest
{
    /**
     * All webhook calls from the Prefect pipeline are trusted internally.
     * Add IP-based or token-based authorization here for production hardening.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Strict validation rules to prevent malformed LLM payloads from crashing
     * the IntegratePrefectData Horizon job.
     *
     * See: docs/architecture.md §Testing Boundary
     */
    public function rules(): array
    {
        return [
            'sentence_job_id' => ['required', 'uuid', 'exists:sentence_jobs,id'],
            'sentence_id' => ['nullable', 'uuid', 'exists:sentences,id'],
            'status' => ['required', 'string', 'in:completed,failed'],

            // Embedding vectors: must be exactly 1024 floats
            // matching the vector(1024) schema columns in the sentences table
            'embedding_ar' => ['required', 'array', 'size:1024'],
            'embedding_ar.*' => ['required', 'numeric'],
            'embedding_id' => ['required', 'array', 'size:1024'],
            'embedding_id.*' => ['required', 'numeric'],

            'category' => ['nullable', 'string', 'max:255'],

            // Lexicon data from CAMeL Tools root extraction
            'lexicon_data' => ['nullable', 'array'],
            'lexicon_data.*.word_raw' => ['required_with:lexicon_data', 'string', 'max:255'],
            'lexicon_data.*.word_clean' => ['required_with:lexicon_data', 'string', 'max:255'],
            'lexicon_data.*.root' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'embedding_ar.size' => 'The embedding_ar must contain exactly 1024 float values (vector dimension mismatch).',
            'embedding_id.size' => 'The embedding_id must contain exactly 1024 float values (vector dimension mismatch).',
        ];
    }
}
