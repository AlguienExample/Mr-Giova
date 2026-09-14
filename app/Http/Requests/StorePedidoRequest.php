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
            'mesa_id'                  => 'required_unless:tipo_pedido,Para_Llevar|nullable|integer|min:1|max:999',
            'tipo_pedido'              => 'sometimes|in:Presencial,Para_Llevar',
            'items'                    => 'required|array|min:1|max:50',
            'items.*.producto_id'      => 'required|exists:productos,id',
            'items.*.cantidad'         => 'required|integer|min:1|max:50',
            'items.*.notas_especiales' => 'nullable|string|max:255',
            'notas'                    => 'nullable|string|max:500'
        ];
    }
}
