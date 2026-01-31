<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreinscripcionRequest extends FormRequest
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
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');
        $documentoRule = $isUpdate
            ? ['required', 'string', 'max:12']
            : ['required', 'string', 'max:12', 'unique:preinscripciones,numero_documento'];

        return [
            // Paso 1: Declaraciones Juradas
            'tiene_dni' => ['required', Rule::in(['SI', 'NO'])],
            'tiene_certificado_estudios' => ['required', Rule::in(['SI', 'NO'])],
            'cursara_5to_anio' => ['required', Rule::in(['SI', 'NO'])],

            // Paso 2: Datos Generales
            'tipo_documento' => ['required', Rule::in(['DNI', 'CARNE_EXTRANJERIA'])],
            'numero_documento' => $documentoRule,
            'apellido_paterno' => ['required', 'string', 'max:100'],
            'apellido_materno' => ['required', 'string', 'max:100'],
            'nombres' => ['required', 'string', 'max:150'],
            'celular_personal' => ['required', 'string', 'size:9', 'regex:/^9[0-9]{8}$/'],
            'celular_apoderado' => ['required', 'string', 'size:9', 'regex:/^9[0-9]{8}$/'],
            'correo_electronico' => ['required', 'email', 'max:150'],

            // 'correo_electronico' => ['required', 'email', 'max:150', Rule::unique('preinscripciones', 'correo_electronico')],

            'genero' => ['required', Rule::in(['MASCULINO', 'FEMENINO', 'OTRO'])],
            'estado_civil' => ['required', Rule::in(['SOLTERO', 'CASADO', 'CONVIVIENTE', 'DIVORCIADO', 'VIUDO'])],

            // Paso 3: Nacimiento y Residencia
            'fecha_nacimiento' => ['required', 'date', 'before:today', 'after:' . now()->subYears(100)->toDateString()],
            'pais_nacimiento' => ['string', 'max:50'],
            // 'departamento_nacimiento' => ['required', 'string', 'max:50'],
            // 'provincia_nacimiento' => ['required', 'string', 'max:100'],
            // 'distrito_nacimiento' => ['required', 'string', 'max:100'],
            // 'departamento_nacimiento' => ['required', 'string', 'size:2'],
            // 'provincia_nacimiento' => ['required', 'string', 'size:4'],
            // 'distrito_nacimiento' => ['required', 'string', 'size:6'],

            // Paso 3: Nacimiento y Residencia
            'departamento_nacimiento' => ['required', 'string', 'size:2', 'regex:/^[0-9]{2}$/'],
            'provincia_nacimiento'    => ['required', 'string', 'size:4', 'regex:/^[0-9]{4}$/'],
            'distrito_nacimiento'     => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
            'ubigeo_nacimiento'       => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],

          


            // 'ubigeo_nacimiento' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
            'pais_residencia' => ['string', 'max:50'],
           // Paso 3: Nacimiento y Residencia
            'departamento_residencia' => ['required', 'string', 'size:2', 'regex:/^[0-9]{2}$/'],
            'provincia_residencia'    => ['required', 'string', 'size:4', 'regex:/^[0-9]{4}$/'],
            'distrito_residencia'     => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
            'ubigeo_residencia'       => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
            'direccion_completa' => ['required', 'string', 'max:500'],

            // Paso 4: Datos de Colegio
            'anio_termino_secundaria' => ['required', 'string', 'size:4', 'regex:/^[0-9]{4}$/'],
            'pais_colegio' => ['required', 'string', 'max:50'],
            'departamento_colegio' => ['required', 'string', 'size:2', 'regex:/^[0-9]{2}$/'],
            'provincia_colegio'    => ['required', 'string', 'size:4', 'regex:/^[0-9]{4}$/'],
            'distrito_colegio'     => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],



            // Colegio
            // 'nombre_colegio' => 'required|string|max:255',
            // 'colegio_id' => 'nullable|string|max:30',

            'nombre_colegio' => ['required', 'string', 'max:255'],

            'nombre_colegio_manual' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if (
                        $this->input('nombre_colegio') === 'OTRO' &&
                        empty(trim($value))
                    ) {
                        $fail('Debe ingresar el nombre del colegio.');
                    }
                },
            ],




            // Paso 5: Información Adicional
            'escuela_profesional' => ['required', 'string', 'max:150'],
            'esta_en_otra_universidad' => ['required', Rule::in(['SI', 'NO'])],
            'identidad_etnica' => ['required', 'string', 'max:100'],
            'tiene_conadis' => ['required', Rule::in(['SI', 'NO'])],
            'lengua_materna' => ['required', 'string', 'max:50'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'numero_documento.unique' => 'Este número de documento ya está registrado.',
            'celular_personal.regex' => 'El celular personal debe comenzar con 9 y tener 9 dígitos.',
            'celular_apoderado.regex' => 'El celular del apoderado debe comenzar con 9 y tener 9 dígitos.',
            'ubigeo_nacimiento.regex' => 'El ubigeo debe contener exactamente 6 dígitos.',
            'ubigeo_nacimiento.size' => 'El ubigeo debe tener 6 dígitos.',
            'anio_termino_secundaria.regex' => 'El año debe ser un número de 4 dígitos.',
            'fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
            'fecha_nacimiento.after' => 'La fecha de nacimiento no es válida.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'tiene_dni' => 'DNI vigente',
            'tiene_certificado_estudios' => 'certificado de estudios',
            'cursara_5to_anio' => '5to año de secundaria',
            'tipo_documento' => 'tipo de documento',
            'numero_documento' => 'número de documento',
            'apellido_paterno' => 'apellido paterno',
            'apellido_materno' => 'apellido materno',
            'nombres' => 'nombres',
            'celular_personal' => 'celular personal',
            'celular_apoderado' => 'celular del apoderado',
            'correo_electronico' => 'correo electrónico',
            'genero' => 'género',
            'estado_civil' => 'estado civil',
            'fecha_nacimiento' => 'fecha de nacimiento',
            'pais_nacimiento' => 'país de nacimiento',
            'departamento_nacimiento' => 'departamento de nacimiento',
            'provincia_nacimiento' => 'provincia de nacimiento',
            'distrito_nacimiento' => 'distrito de nacimiento',
            'ubigeo_nacimiento' => 'ubigeo de nacimiento',
            'pais_residencia' => 'país de residencia',
            'departamento_residencia' => 'departamento de residencia',
            'provincia_residencia' => 'provincia de residencia',
            'distrito_residencia' => 'distrito de residencia',
            'direccion_completa' => 'dirección completa',
            'anio_termino_secundaria' => 'año de término de secundaria',
            'pais_colegio' => 'país del colegio',
            'departamento_colegio' => 'departamento del colegio',
            'provincia_colegio' => 'provincia del colegio',
            'distrito_colegio' => 'distrito del colegio',
            'nombre_colegio' => 'nombre del colegio',
            'escuela_profesional' => 'escuela profesional',
            'esta_en_otra_universidad' => 'otra universidad',
            'identidad_etnica' => 'identidad étnica',
            'tiene_conadis' => 'carnet CONADIS',
            'lengua_materna' => 'lengua materna',
        ];
    }
}
