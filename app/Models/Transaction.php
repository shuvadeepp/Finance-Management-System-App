<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $table = 'transactions';

    public $timestamps = false;

    protected $fillable = [
        'transaction_date',
        'amount',
        'transaction_type',
        'description',
        'category_id',
        'created_by'
    ];
}