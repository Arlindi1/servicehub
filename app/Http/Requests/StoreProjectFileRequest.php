<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectFileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project
            && ($this->user()?->can('create', [ProjectFile::class, $project]) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $allowedFileTypes = ProjectFile::FILE_TYPES;

        if ($this->user()?->hasRole('Client')) {
            $allowedFileTypes = ['Client Upload'];
        }

        return [
            'file_type' => ['required', 'string', Rule::in($allowedFileTypes)],
            'file' => [
                'required',
                'file',
                'max:20480',
                'mimes:pdf,png,jpg,jpeg,docx,xlsx,zip',
            ],
        ];
    }
}

