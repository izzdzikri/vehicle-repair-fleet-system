<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $vehicle = $this->route('vehicle');

        return $vehicle ? $this->user()?->can('update', $vehicle) : false;
    }

    public function rules(): array
    {
        return [
            'brand' => 'required|string|max:60',
            'model' => 'required|string|max:60',
            'year' => 'required|integer|min:1970|max:'.(date('Y') + 1),
            'mileage' => 'nullable|numeric|min:0',
            'user_id' => 'nullable|exists:users,id',
        ];
    }
}
