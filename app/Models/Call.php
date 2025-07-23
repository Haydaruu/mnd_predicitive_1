<?php

namespace App\Models;

use App\Models\Agent;
use App\Models\CallerId;
use App\Models\Nasabah;
use App\Models\Campaign;

use Illuminate\Database\Eloquent\Model;

class Call extends Model
{
    protected $fillable = [
        'campaign_id',
        'nasabah_id',
        'agent_id',
        'caller_id',
        'status',
        'call_started_at',
        'call_ended_at',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function nasabah()
    {
        return $this->belongsTo(Nasabah::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function callerId()
    {
        return $this->belongsTo(CallerId::class, 'caller_id');
    }
}
