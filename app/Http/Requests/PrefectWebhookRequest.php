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
            'status' => ['required', 'string', 'in:completed,failed'],
            'error' => ['nullable', 'string'],

            // Optional fields if we ever decide to pass data back through webhook again
            'sentence_id' => ['nullable', 'uuid', 'exists:sentences,id'],
            'translation' => ['nullable', 'string'],
            'transliteration' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [];
    }
}
