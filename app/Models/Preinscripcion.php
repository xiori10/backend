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
     * Tabla asociada al modelo.
     */
    protected $table = 'preinscripciones';

    /**
     * Estados posibles de la preinscripción.
     * Se usan constantes para evitar errores tipográficos.
     */
    public const ESTADO_PENDIENTE = 'PENDIENTE';
    public const ESTADO_APROBADO = 'APROBADO';
    public const ESTADO_RECHAZADO = 'RECHAZADO';

    /**
     * Campos que pueden ser asignados masivamente.
     * SOLO datos que el usuario puede modificar.
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
     * Campos ocultos en las respuestas JSON.
     * Protege datos sensibles en APIs públicas.
     */
    protected $hidden = [
        'codigo_seguridad',
        'deleted_at',
    ];

    /**
     * Atributos agregados automáticamente al convertir a JSON.
     */
    protected $appends = [
        'nombre_completo',
        'edad',
    ];

    /**
     * Casts de tipos para asegurar consistencia de datos.
     */
    protected $casts = [
        'fecha_nacimiento' => 'date',
        'puede_modificar' => 'boolean',
        'fecha_modificacion' => 'datetime',

        // UBIGEO como string
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
     * Evento del modelo: antes de crear genera automáticamente
     * un código de seguridad único si no existe.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($preinscripcion) {
            if (empty($preinscripcion->codigo_seguridad)) {
                $preinscripcion->codigo_seguridad = static::generarCodigoSeguridad();
            }

            // Estado inicial por defecto
            $preinscripcion->estado = self::ESTADO_PENDIENTE;
            $preinscripcion->puede_modificar = true;
        });
    }

    /**
     * Genera un código de seguridad único de 5 caracteres.
     */
    public static function generarCodigoSeguridad(): string
    {
        do {
            $codigo = strtoupper(Str::random(5));
        } while (static::where('codigo_seguridad', $codigo)->exists());

        return $codigo;
    }

    /**
     * Accessor: devuelve el nombre completo.
     */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombres} {$this->apellido_paterno} {$this->apellido_materno}";
    }

    /**
     * Accessor: calcula automáticamente la edad.
     */
    public function getEdadAttribute(): int
    {
        return $this->fecha_nacimiento?->age ?? 0;
    }

    /**
     * Determina si el postulante aún puede modificar sus datos.
     */
    public function puedeModificar(): bool
    {
        return $this->puede_modificar && $this->estado === self::ESTADO_PENDIENTE;
    }

    /**
     * Marca la preinscripción como modificada
     * y bloquea futuras ediciones.
     * No usa mass assignment por seguridad.
     */
    public function marcarComoModificado(): void
    {
        $this->puede_modificar = false;
        $this->fecha_modificacion = now();
        $this->save();
    }

    /**
     * Scope para filtrar por estado.
     */
    public function scopeEstado($query, string $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope para filtrar por escuela profesional.
     */
    public function scopeEscuelaProfesional($query, string $escuela)
    {
        return $query->where('escuela_profesional', $escuela);
    }

    /**
     * Scope de búsqueda por DNI o nombres.
     */
    public function scopeSearch($query, $search)
    {
        if (!$search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('numero_documento', 'like', "%{$search}%")
                ->orWhere('nombres', 'like', "%{$search}%")
                ->orWhere('apellido_paterno', 'like', "%{$search}%")
                ->orWhere('apellido_materno', 'like', "%{$search}%");
        });
    }

    /**
     * Scope para obtener preinscripciones recientes.
     */
    public function scopeRecientes($query, int $dias = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($dias));
    }
}
