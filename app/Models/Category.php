<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $table = 'categories';

    protected $fillable = [
        'category_name',
        'category_type',
        'is_default',
        'created_by',
    ];

    protected $dates = ['deleted_at'];

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'category_id');
    }

    public function budgets()
    {
        return $this->hasMany(Budget::class, 'category_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
