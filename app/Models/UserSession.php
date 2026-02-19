<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSession extends Model
{
    protected $fillable = [
        'user_id',
        'token_id',
        'ip_address',
        'user_agent',
        'login_at',
        'logout_at',
        'last_activity'
    ];
}
