<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $typeChoice = $this->input('type_choice', '');

        $type = match ($typeChoice) {
            '__custom__' => $this->input('custom_type', ''),
            '' => null,
            default => $typeChoice,
        };

        $this->merge(['type' => $type ?: null]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:500'],
            'task_date' => ['required', 'date', 'before_or_equal:today'],
            'type' => ['nullable', 'string', 'max:100'],
        ];
    }
}
