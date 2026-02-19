<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    protected $fillable = [
        'tiempo_sesion',
        'max_intentos_login',
    ];
}
