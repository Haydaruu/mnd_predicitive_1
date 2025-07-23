<?php

namespace App\Models;

use App\Models\Call;
use App\Models\activeCall;
use App\Models\User;

use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    protected $fillable = [
        'user_id',
        'name', 
        'extension', 
        'status'
    ];

    public function calls()
    {
        return $this->hasMany(Call::class);
    }
    public function activeCall()
    {
        return $this->hasOne(Call::class)->where('status', 'connected');
    }
    public function user()
    {
        return $this->hasOne(User::class);
    }
}
