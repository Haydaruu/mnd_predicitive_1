<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nasbah extends Model
{
    protected $fillable = ['name', 
    'phone', 
    'outstanding', 
    'denda', 
    'data_json', 
    'catatan',
    'campaign_id'
];

    public function calls()
    {
        return $this->hasMany(Call::class);
    }
    public function campaign()
    {
    return $this->belongsTo(Campaign::class);
    }
}
