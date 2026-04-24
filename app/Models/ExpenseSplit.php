<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseSplit extends Model
{
    protected $table = 'expense_splits';

    protected $fillable = [
        'user_id',
        'expense_id',
        'amount_owed',
        'is_paid'
    ];
}
