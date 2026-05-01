<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseSplit extends Model
{
    protected $table = 'expense_splits';

    protected $fillable = [
        'user_id',
        'expense_id',
        'amount_owed',
        'is_paid'
    ];

    public function user() : BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function expense() : BelongsTo {
        return $this->belongsTo(Expense::class);
    }
}
