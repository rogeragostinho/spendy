<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Expense extends Model
{
    protected $fillable = [
        'group_id',
        'paid_by',
        'amount',
        'description',
        //'category'
    ];

    public function users(): BelongsToMany {
        return $this->belongsToMany(User::class, 'expense_splits')->withPivot('amount_owed', 'is_paid');
    }
}
