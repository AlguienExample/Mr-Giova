<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre'   => 'required|string|max:100',
            'fecha'    => 'required|date|after_or_equal:today',
            'hora'     => 'required|string',
            'personas' => 'required|integer|min:1|max:50',
            'mesa_id'  => 'required|integer|exists:mesas,id',
            'notas'    => 'nullable|string|max:500',
        ];
    }

    /**
     * Custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'nombre.required'      => 'El nombre del cliente es obligatorio.',
            'fecha.required'       => 'La fecha es obligatoria.',
            'fecha.after_or_equal' => 'La fecha no puede ser anterior a hoy.',
            'hora.required'        => 'La hora es obligatoria.',
            'personas.required'    => 'El número de comensales es obligatorio.',
            'personas.min'         => 'Debe haber al menos 1 comensal.',
            'mesa_id.required'     => 'Debe seleccionar una mesa para la reserva.',
            'mesa_id.exists'       => 'La mesa seleccionada no existe.',
        ];
    }
}
