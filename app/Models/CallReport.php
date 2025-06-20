<?php

namespace App\Models;

use App\Models\Campaign;
use App\Models\Agent;

use Illuminate\Database\Eloquent\Model;

class CallReport extends Model
{
    protected $fillable = [
        'campaign_id',
        'agent_id',
        'total_calls',
        'answered_calls',
        'failed_calls',
        'busy_calls',
        'no_answer_calls',
        'total_talk_time',
        'average_talk_time',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
        'total_talk_time' => 'integer',
        'average_talk_time' => 'float',
    ];

     public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }
}