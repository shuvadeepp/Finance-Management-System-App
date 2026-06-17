<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    protected $table = 'users';

    /* protected $fillable = [
        'username',
        'password_hash',
        'role',
        'is_active',
    ]; */

    protected $fillable = [
        'username',
        'password_hash',
        'role',
        'is_active',
        'github_id',
        'email',
        'avatar',
    ];

    protected $hidden = [
        'password_hash',
    ];

    // Map Laravel's default 'password' field to your 'password_hash'
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    // JWT required methods
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}