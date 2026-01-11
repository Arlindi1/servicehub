<?php

namespace App\Http\Requests;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

class UpdateTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task instanceof Task
            && ($this->user()?->can('update', $task) ?? false);
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
            'status' => ['required', 'string', Rule::in(Task::STATUSES)],
            'assigned_to_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('organization_id', $organizationId)),
            ],
            'due_date' => ['nullable', 'date'],
        ];
    }

    public function withValidator(ValidationValidator $validator): void
    {
        $validator->after(function (ValidationValidator $validator): void {
            $organizationId = $this->user()?->organization_id;
            $assigneeId = $this->input('assigned_to_user_id');

            if (! $organizationId || ! $assigneeId) {
                return;
            }

            $assigneeIsStaff = User::query()
                ->where('organization_id', $organizationId)
                ->whereHas('roles', fn ($query) => $query->whereIn('name', ['Owner', 'Staff']))
                ->where('id', $assigneeId)
                ->exists();

            if (! $assigneeIsStaff) {
                $validator->errors()->add('assigned_to_user_id', 'Only staff users in your organization can be assigned.');
            }
        });
    }
}
