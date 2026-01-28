<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('preinscripciones', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Paso 1: Declaraciones Juradas
            |--------------------------------------------------------------------------
            */
            $table->enum('tiene_dni', ['SI', 'NO']);
            $table->enum('tiene_certificado_estudios', ['SI', 'NO']);
            $table->enum('cursara_5to_anio', ['SI', 'NO']);

            /*
            |--------------------------------------------------------------------------
            | Paso 2: Datos Generales
            |--------------------------------------------------------------------------
            */
            $table->enum('tipo_documento', ['DNI', 'CARNE_EXTRANJERIA']);
            $table->string('numero_documento', 12)->unique();
            $table->string('apellido_paterno', 100);
            $table->string('apellido_materno', 100);
            $table->string('nombres', 150);
            $table->string('celular_personal', 9);
            $table->string('celular_apoderado', 9);
            $table->string('correo_electronico', 150);
            $table->enum('genero', ['MASCULINO', 'FEMENINO', 'OTRO']);
            $table->enum('estado_civil', ['SOLTERO', 'CASADO', 'CONVIVIENTE', 'DIVORCIADO', 'VIUDO']);

            /*
            |--------------------------------------------------------------------------
            | Paso 3: Nacimiento
            |--------------------------------------------------------------------------
            */
            $table->date('fecha_nacimiento');
            $table->string('pais_nacimiento', 50)->default('PERÚ');

            // Códigos UBIGEO
            $table->string('departamento_nacimiento', 2);
            $table->string('provincia_nacimiento', 4);
            $table->string('distrito_nacimiento', 6);
            $table->string('ubigeo_nacimiento', 6);

            // Nombres UBIGEO (cacheados)
            $table->string('departamento_nacimiento_nombre', 100)->nullable();
            $table->string('provincia_nacimiento_nombre', 100)->nullable();
            $table->string('distrito_nacimiento_nombre', 100)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Paso 3: Residencia
            |--------------------------------------------------------------------------
            */
            $table->string('pais_residencia', 50)->default('PERÚ');

            // Códigos UBIGEO
            $table->string('departamento_residencia', 2);
            $table->string('provincia_residencia', 4);
            $table->string('distrito_residencia', 6);
            $table->string('ubigeo_residencia', 6);

            // Nombres UBIGEO (cacheados)
            $table->string('departamento_residencia_nombre', 100)->nullable();
            $table->string('provincia_residencia_nombre', 100)->nullable();
            $table->string('distrito_residencia_nombre', 100)->nullable();

            $table->text('direccion_completa');

            /*
            |--------------------------------------------------------------------------
            | Paso 4: Datos de Colegio
            |--------------------------------------------------------------------------
            */
            $table->string('anio_termino_secundaria', 4);
            $table->string('pais_colegio', 50)->default('PERÚ');

            // Códigos UBIGEO
            $table->string('departamento_colegio', 2);
            $table->string('provincia_colegio', 4);
            $table->string('distrito_colegio', 6);

            // Nombres UBIGEO (cacheados)
            $table->string('departamento_colegio_nombre', 100)->nullable();
            $table->string('provincia_colegio_nombre', 100)->nullable();
            $table->string('distrito_colegio_nombre', 100)->nullable();

            // Colegio
            $table->string('colegio_id', 30)->nullable();
            $table->string('nombre_colegio', 255);

            /*
            |--------------------------------------------------------------------------
            | Paso 5: Información Adicional
            |--------------------------------------------------------------------------
            */
            $table->string('escuela_profesional', 150);
            $table->enum('esta_en_otra_universidad', ['SI', 'NO']);
            $table->string('identidad_etnica', 100);
            $table->enum('tiene_conadis', ['SI', 'NO']);
            $table->string('lengua_materna', 50);

            /*
            |--------------------------------------------------------------------------
            | Control
            |--------------------------------------------------------------------------
            */
            $table->string('codigo_seguridad', 5)->unique();
            $table->boolean('puede_modificar')->default(true);
            $table->enum('estado', ['PENDIENTE', 'PAGADO', 'INSCRITO', 'RECHAZADO'])->default('PENDIENTE');
            $table->timestamp('fecha_modificacion')->nullable();

            $table->timestamps();
            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | Índices
            |--------------------------------------------------------------------------
            */
            $table->index('numero_documento');
            $table->index('codigo_seguridad');
            $table->index('estado');
            $table->index('escuela_profesional');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preinscripciones');
    }
};
