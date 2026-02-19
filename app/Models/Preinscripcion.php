<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Preinscripcion extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * ---------------------------------------------------------
     * CONFIGURACIÓN BÁSICA DEL MODELO
     * ---------------------------------------------------------
     */

    // Tabla asociada en base de datos
    protected $table = 'preinscripciones';


    /**
     * ---------------------------------------------------------
     * DEFINICIÓN DE ESTADOS (FUENTE ÚNICA DE VERDAD)
     * ---------------------------------------------------------
     * Aquí se definen todos los estados posibles del sistema.
     * Si mañana agregas PAGADO, solo lo agregas aquí.
     */

    // Estado inicial cuando el alumno se registra
    public const ESTADO_PENDIENTE = 'PENDIENTE';

    // Estado cuando el admin aprueba manualmente
    public const ESTADO_INSCRITO = 'INSCRITO';

    // Estado cuando el admin rechaza la solicitud
    public const ESTADO_RECHAZADO = 'RECHAZADO';

    /**
     * Lista oficial de estados válidos.
     * Se usa en validaciones del controlador.
     */
    public const ESTADOS = [
        self::ESTADO_PENDIENTE,
        self::ESTADO_INSCRITO,
        self::ESTADO_RECHAZADO,
    ];

    /**
     * ---------------------------------------------------------
     * TRANSICIONES PERMITIDAS ENTRE ESTADOS
     * ---------------------------------------------------------
     * Define qué estados pueden cambiar a otros.
     * Esto blinda la lógica del negocio.
     */
    public const TRANSICIONES = [

        // Desde PENDIENTE se puede aprobar o rechazar
        self::ESTADO_PENDIENTE => [
            self::ESTADO_INSCRITO,
            self::ESTADO_RECHAZADO,
        ],

        // Estados finales no pueden cambiar
        self::ESTADO_INSCRITO => [],
        self::ESTADO_RECHAZADO => [],
    ];


    /**
     * ---------------------------------------------------------
     * MASS ASSIGNMENT
     * ---------------------------------------------------------
     * Solo campos que el usuario puede modificar.
     * Campos críticos del sistema NO deben ir aquí.
     */
    protected $fillable = [
        // Paso 1
        'tiene_dni',
        'tiene_certificado_estudios',
        'cursara_5to_anio',

        // Paso 2
        'tipo_documento',
        'numero_documento',
        'apellido_paterno',
        'apellido_materno',
        'nombres',
        'celular_personal',
        'celular_apoderado',
        'correo_electronico',
        'genero',
        'estado_civil',

        // Paso 3
        'fecha_nacimiento',
        'pais_nacimiento',
        'departamento_nacimiento',
        'provincia_nacimiento',
        'distrito_nacimiento',
        'departamento_nacimiento_nombre',
        'provincia_nacimiento_nombre',
        'distrito_nacimiento_nombre',
        'ubigeo_nacimiento',
        'pais_residencia',
        'departamento_residencia',
        'provincia_residencia',
        'distrito_residencia',
        'departamento_residencia_nombre',
        'provincia_residencia_nombre',
        'distrito_residencia_nombre',
        'ubigeo_residencia',
        'direccion_completa',

        // Paso 4
        'anio_termino_secundaria',
        'pais_colegio',
        'departamento_colegio',
        'provincia_colegio',
        'distrito_colegio',
        'departamento_colegio_nombre',
        'provincia_colegio_nombre',
        'distrito_colegio_nombre',
        'colegio_id',
        'nombre_colegio',

        // Paso 5
        'escuela_profesional',
        'esta_en_otra_universidad',
        'identidad_etnica',
        'tiene_conadis',
        'lengua_materna',
    ];


    /**
     * Campos ocultos en respuestas JSON
     */
    protected $hidden = [
        'codigo_seguridad',
        'deleted_at',
    ];


    /**
     * Atributos calculados automáticamente
     */
    protected $appends = [
        'nombre_completo',
        'edad',
    ];


    /**
     * Conversión automática de tipos
     */
    protected $casts = [
        'fecha_nacimiento' => 'date',
        'puede_modificar' => 'boolean',
        'fecha_modificacion' => 'datetime',

        'pais_nacimiento' => 'string',
        'departamento_nacimiento' => 'string',
        'provincia_nacimiento' => 'string',
        'distrito_nacimiento' => 'string',
        'ubigeo_nacimiento' => 'string',

        'pais_residencia' => 'string',
        'departamento_residencia' => 'string',
        'provincia_residencia' => 'string',
        'distrito_residencia' => 'string',

        'pais_colegio' => 'string',
        'departamento_colegio' => 'string',
        'provincia_colegio' => 'string',
        'distrito_colegio' => 'string',
    ];


    /**
     * ---------------------------------------------------------
     * EVENTOS DEL MODELO
     * ---------------------------------------------------------
     * Se ejecuta automáticamente antes de crear el registro.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($preinscripcion) {

            // Genera código único si no existe
            if (empty($preinscripcion->codigo_seguridad)) {
                $preinscripcion->codigo_seguridad = static::generarCodigoSeguridad();
            }

            // Estado inicial obligatorio
            $preinscripcion->estado = self::ESTADO_PENDIENTE;

            // Permitir edición mientras esté pendiente
            $preinscripcion->puede_modificar = true;
        });
    }


    /**
     * Genera un código de seguridad único
     */
    public static function generarCodigoSeguridad(): string
    {
        do {
            $codigo = strtoupper(Str::random(5));
        } while (static::where('codigo_seguridad', $codigo)->exists());

        return $codigo;
    }


    /**
     * Accessor: Devuelve nombre completo
     */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombres} {$this->apellido_paterno} {$this->apellido_materno}";
    }


    /**
     * Accessor: Calcula edad automáticamente
     */
    public function getEdadAttribute(): int
    {
        return $this->fecha_nacimiento?->age ?? 0;
    }


    /**
     * Determina si puede modificar datos
     * Solo permitido si está PENDIENTE
     */
    public function puedeModificar(): bool
    {
        return $this->puede_modificar && $this->estado === self::ESTADO_PENDIENTE;
    }


    /**
     * Bloquea futuras ediciones
     */
    public function marcarComoModificado(): void
    {
        $this->puede_modificar = false;
        $this->fecha_modificacion = now();
        $this->save();
    }


    /**
     * Scope para filtrar por estado
     */
    public function scopeEstado($query, string $estado)
    {
        return $query->where('estado', $estado);
    }


    /**
     * Scope para filtrar por escuela profesional
     */
    public function scopeEscuelaProfesional($query, string $escuela)
    {
        return $query->where('escuela_profesional', $escuela);
    }


    /**
     * Scope para búsqueda por DNI o nombres
     */
    public function scopeSearch($query, $search)
    {
        if (!$search) return $query;

        return $query->where(function ($q) use ($search) {
            $q->where('numero_documento', 'like', "%{$search}%")
                ->orWhere('nombres', 'like', "%{$search}%")
                ->orWhere('apellido_paterno', 'like', "%{$search}%")
                ->orWhere('apellido_materno', 'like', "%{$search}%");
        });
    }


    /**
     * Scope para registros recientes
     */
    public function scopeRecientes($query, int $dias = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($dias));
    }


    /**
     * Verifica si puede cambiar al nuevo estado
     * Usa la matriz TRANSICIONES
     */
    public function puedeCambiarA(string $nuevoEstado): bool
    {
        return in_array(
            $nuevoEstado,
            self::TRANSICIONES[$this->estado] ?? []
        );
    }


    /**
     * Determina si el estado es final
     * (Preparado para futuro con más estados)
     */
    public function esEstadoFinal(): bool
    {
        return empty(self::TRANSICIONES[$this->estado]);
    }
}
