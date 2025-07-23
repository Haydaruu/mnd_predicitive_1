<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallerId extends Model
{
    protected $fillable = ['number', 'is_active'];

    public function calls()
    {
        return $this->hasMany(Call::class, 'caller_id');
    }
}
