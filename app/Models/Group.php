<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Group extends Model
{
    protected $fillable = [
        'name',
        'invite_code'
    ];

    public function users(): BelongsToMany {
        return $this->belongsToMany(User::class)->withPivot('role', 'joined_at');
    }
}
