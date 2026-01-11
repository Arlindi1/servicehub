<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

class StoreProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Project::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => is_string($this->title) ? trim($this->title) : $this->title,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $organizationId = $this->user()?->organization_id;

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'status' => ['required', 'string', Rule::in(Project::STATUSES)],
            'priority' => ['required', 'string', Rule::in(Project::PRIORITIES)],
            'due_date' => ['nullable', 'date'],
            'client_id' => [
                'required',
                'integer',
                Rule::exists('clients', 'id')->where(fn ($query) => $query->where('organization_id', $organizationId)),
            ],
            'staff_ids' => ['array'],
            'staff_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('organization_id', $organizationId)),
            ],
        ];
    }

    public function withValidator(ValidationValidator $validator): void
    {
        $validator->after(function (ValidationValidator $validator): void {
            $organizationId = $this->user()?->organization_id;

            if (! $organizationId) {
                return;
            }

            $staffIds = is_array($this->input('staff_ids'))
                ? collect($this->input('staff_ids'))->filter()->map(fn ($id) => (int) $id)->unique()->values()
                : collect();

            if ($staffIds->isEmpty()) {
                return;
            }

            $allowedCount = User::query()
                ->where('organization_id', $organizationId)
                ->whereHas('roles', fn ($query) => $query->whereIn('name', ['Owner', 'Staff']))
                ->whereIn('id', $staffIds->all())
                ->count();

            if ($allowedCount !== $staffIds->count()) {
                $validator->errors()->add('staff_ids', 'Only staff users in your organization can be assigned.');
            }
        });
    }
}
