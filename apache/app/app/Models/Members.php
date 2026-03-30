<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Members extends Authenticatable
{
    use Notifiable;

    protected $table = "bei_users";
    public $timestamps = false;
    protected $rememberTokenName = false;
    protected $guard = 'members';

    protected $fillable = [
        'email', 'username', 'last_login', 'last_ip', 'password', 'status', 'verify_token', 'country'
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'email'=>'string',
        'username'=>'string',
        'password'=>'string',
        'last_login'=>'datetime',
        'last_ip'=>'string',
        'country'=>'integer',
        'status'=>'integer',
        'verify_token'=>'string'
    ];




}