<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReplaceDocumentFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'mimetypes:application/pdf,image/jpeg,image/png',
                'max:5120',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.max' => 'The document file may not be greater than 5 MB.',
            'file.mimes' => 'The document must be a file of type: PDF, JPG, or PNG.',
            'file.mimetypes' => 'The uploaded file has an invalid MIME type. Only real PDF and image files are accepted.',
        ];
    }
}
