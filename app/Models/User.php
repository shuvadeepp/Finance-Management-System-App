<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use SoftDeletes;

    protected $table = 'users';

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

    protected $dates = ['deleted_at'];

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'created_by');
    }

    public function budgets()
    {
        return $this->hasMany(Budget::class, 'created_by');
    }

    public function categories()
    {
        return $this->hasMany(Category::class, 'created_by');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'user_id');
    }
}
