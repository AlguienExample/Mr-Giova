<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePedidoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // We'll rely on middleware for now
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mesa_id'                  => 'required',
            'items'                    => 'required|array|min:1',
            'items.*.producto_id'      => 'required|exists:productos,id',
            'items.*.cantidad'         => 'required|integer|min:1',
            'items.*.notas_especiales' => 'nullable|string|max:255',
            'notas'                    => 'nullable|string'
        ];
    }
}
