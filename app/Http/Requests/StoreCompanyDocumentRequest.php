<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:ssm_certificate,delivery_order,other'],
            'reference_no' => ['required', 'string', 'max:100'],
            'issue_date' => ['required', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
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
            'expiry_date.after_or_equal' => 'The expiry date must be a date after or equal to the issue date.',
        ];
    }
}
