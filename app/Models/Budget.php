<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    protected $table = 'budgets';

    public $timestamps = false;

    protected $fillable = [
        'category_id',
        'budget_month',
        'budget_year',
        'budget_amount',
        'created_by'
    ];
}