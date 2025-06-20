<?php

namespace App\Models;

use App\Models\Call;
use App\Models\Nasbah;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $fillable = [
        'campaign_name', 
        'product_type', 
        'dialing_type',
        'created_by',
        'keterangan',
        'is_active',
        'retry_count',
        'file_path',
        'status',
        'started_at',
        'stopped_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'started_at' => 'datetime',
        'stopped_at' => 'datetime',
    ];

    public function calls()
    {
        return $this->hasMany(Call::class);
    }
    
    public function nasbahs() 
    {
        return $this->hasMany(Nasbah::class);
    }

    // Scope untuk campaign yang sedang aktif
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope untuk campaign yang sedang running
    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    // Check if campaign can be started
    public function canBeStarted(): bool
    {
        return in_array($this->status, ['pending', 'stopped']) && 
               $this->nasbahs()->where('is_called', false)->count() > 0;
    }

    // Check if campaign can be paused
    public function canBePaused(): bool
    {
        return $this->status === 'running';
    }

    // Check if campaign can be resumed
    public function canBeResumed(): bool
    {
        return $this->status === 'paused';
    }

    // Check if campaign can be stopped
    public function canBeStopped(): bool
    {
        return in_array($this->status, ['running', 'paused']);
    }

    // Get campaign progress percentage
    public function getProgressPercentage(): float
    {
        $total = $this->nasbahs()->count();
        if ($total === 0) return 0;
        
        $called = $this->nasbahs()->where('is_called', true)->count();
        return ($called / $total) * 100;
    }

    // Get answer rate percentage
    public function getAnswerRate(): float
    {
        $totalCalls = $this->calls()->count();
        if ($totalCalls === 0) return 0;
        
        $answeredCalls = $this->calls()->where('status', 'answered')->count();
        return ($answeredCalls / $totalCalls) * 100;
    }
}