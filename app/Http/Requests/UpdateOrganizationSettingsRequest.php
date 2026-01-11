<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $organization = $this->user()?->organization;

        if (! $organization) {
            return false;
        }

        return $this->user()?->can('update', $organization) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $invoicePrefix = is_string($this->invoice_prefix) ? strtoupper(trim($this->invoice_prefix)) : $this->invoice_prefix;
        $brandColor = is_string($this->brand_color) ? trim($this->brand_color) : $this->brand_color;
        $billingEmail = is_string($this->billing_email) ? trim($this->billing_email) : $this->billing_email;

        $this->merge([
            'name' => is_string($this->name) ? trim($this->name) : $this->name,
            'brand_color' => $brandColor === '' ? null : $brandColor,
            'invoice_prefix' => $invoicePrefix === '' ? null : $invoicePrefix,
            'billing_email' => $billingEmail === '' ? null : $billingEmail,
        ]);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'brand_color' => ['nullable', 'string', 'max:20', 'regex:/^#([0-9a-fA-F]{6}|[0-9a-fA-F]{3})$/'],
            'logo' => ['nullable', 'file', 'image', 'max:4096'],
            'invoice_prefix' => ['nullable', 'string', 'max:10', 'regex:/^[A-Z0-9]+$/'],
            'invoice_due_days_default' => ['required', 'integer', 'min:1', 'max:365'],
            'billing_email' => ['nullable', 'string', 'lowercase', 'email', 'max:255'],
        ];
    }
}
