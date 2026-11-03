<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('invoice.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:cash,bank_transfer,card,online',
            'reference_no' => 'nullable|string|max:100',
            'paid_at' => 'nullable|date',
        ];
    }
}
