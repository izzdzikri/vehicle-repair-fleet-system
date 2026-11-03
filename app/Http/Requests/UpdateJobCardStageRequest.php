<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobCardStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorized via JobCardPolicy
    }

    public function rules(): array
    {
        return [
            'current_stage' => 'required|in:received,diagnosing,waiting_parts,repairing,quality_check,completed',
        ];
    }
}
