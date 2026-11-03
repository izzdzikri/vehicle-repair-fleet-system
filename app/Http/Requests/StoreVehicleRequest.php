<?php

namespace App\Http\Requests;

use App\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Vehicle::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'plate_number' => 'required|string|max:20|unique:vehicles,plate_number',
            'brand' => 'required|string|max:60',
            'model' => 'required|string|max:60',
            'year' => 'required|integer|min:1970|max:'.(date('Y') + 1),
            'mileage' => 'nullable|numeric|min:0',
            'user_id' => 'nullable|exists:users,id',
        ];
    }
}
