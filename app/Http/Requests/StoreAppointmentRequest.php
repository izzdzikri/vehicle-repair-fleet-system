<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Appointment::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'vehicle_id' => 'required|exists:vehicles,id',
            'date' => 'required|date|after:today',
            'time' => 'required',
            'service_type' => 'required|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
