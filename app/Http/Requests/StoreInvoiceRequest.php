<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

class StoreInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Invoice::class) ?? false;
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
            'client_id' => [
                'required',
                'integer',
                Rule::exists('clients', 'id')->where(fn ($query) => $query->where('organization_id', $organizationId)),
            ],
            'project_id' => [
                'nullable',
                'integer',
                Rule::exists('projects', 'id')->where(fn ($query) => $query->where('organization_id', $organizationId)),
            ],
            'due_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'items.*.unit_price' => ['required', 'integer', 'min:0', 'max:1000000000'],
        ];
    }

    public function withValidator(ValidationValidator $validator): void
    {
        $validator->after(function (ValidationValidator $validator): void {
            $organizationId = $this->user()?->organization_id;
            $clientId = $this->input('client_id');
            $projectId = $this->input('project_id');

            if (! $organizationId || ! $clientId || ! $projectId) {
                return;
            }

            $projectMatchesClient = \App\Models\Project::query()
                ->where('organization_id', $organizationId)
                ->where('id', $projectId)
                ->where('client_id', $clientId)
                ->exists();

            if (! $projectMatchesClient) {
                $validator->errors()->add('project_id', 'Selected project must belong to the chosen client.');
            }
        });
    }
}

