<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Managers extends Authenticatable
{
    use Notifiable;

    protected $table = "bei_managers";
    public $timestamps = false;
    protected $rememberTokenName = false;
    protected $guard = 'managers';

    protected $fillable = [
        'id', 'email', 'last_login', 'last_ip', 'main', 'country'
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'id'=>'integer',
        'email'=>'string',
        'password'=>'string',
        'last_login'=>'datetime',
        'last_ip'=>'string',
        'main'=>'integer',
        'country'=>'integer'
    ];




}