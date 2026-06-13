<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeProject extends Model
{
    protected $table = 'employee_projects';

    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'project_id',
        'assigned_date'
    ];
}