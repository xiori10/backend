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
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'preinscripciones';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
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

    protected $hidden = [
        'apellido_paterno',
        'apellido_materno',
        'nombres',
        'fecha_nacimiento',
        'celular_personal',
        'celular_apoderado',
        'correo_electronico',
        'departamento_nacimiento',
        'provincia_nacimiento',
        'distrito_nacimiento',
        'departamento_residencia',
        'provincia_residencia',
        'distrito_residencia',
        'numero_documento',
        'tipo_documento',
        'direccion_completa',
        'ubigeo_nacimiento',
        'ubigeo_residencia',

    ];





    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_nacimiento' => 'date',
        'puede_modificar' => 'boolean',
        'fecha_modificacion' => 'datetime',

        // UBIGEO = STRING
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
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Generar código de seguridad automáticamente antes de crear
        static::creating(function ($preinscripcion) {
            if (empty($preinscripcion->codigo_seguridad)) {
                $preinscripcion->codigo_seguridad = static::generarCodigoSeguridad();
            }
        });
    }

    /**
     * Generar un código de seguridad único de 5 caracteres alfanuméricos.
     *
     * @return string
     */
    public static function generarCodigoSeguridad(): string
    {
        do {
            $codigo = strtoupper(Str::random(5));
        } while (static::where('codigo_seguridad', $codigo)->exists());

        return $codigo;
    }

    /**
     * Obtener el nombre completo del postulante.
     *
     * @return string
     */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombres} {$this->apellido_paterno} {$this->apellido_materno}";
    }

    /**
     * Calcular la edad del postulante.
     *
     * @return int
     */
    public function getEdadAttribute(): int
    {
        return $this->fecha_nacimiento->age;
    }

    /**
     * Verificar si puede modificar sus datos.
     *
     * @return bool
     */
    public function puedeModificar(): bool
    {
        return $this->puede_modificar && $this->estado === 'PENDIENTE';
    }

    /**
     * Marcar que ya no puede modificar (usó su única oportunidad).
     *
     * @return void
     */
    public function marcarComoModificado(): void
    {
        $this->update([
            'puede_modificar' => false,
            'fecha_modificacion' => now(),
        ]);
    }

    /**
     * Scope para filtrar por estado.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $estado
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEstado($query, string $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope para filtrar por escuela profesional.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $escuela
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEscuelaProfesional($query, string $escuela)
    {
        return $query->where('escuela_profesional', $escuela);
    }

    /**
     * Scope para obtener pre-inscripciones recientes.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $dias
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecientes($query, int $dias = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($dias));
    }
}