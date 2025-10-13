<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TimeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 
        'clock_in', 
        'clock_out', 
        'break_in', 
        'break_out', 
        'duration', 
        'status', 
        'note', 
        'latitude', 
        'longitude', 
        'address', 
        'ip_address', 
        'device' 
    ];

    protected $casts = [
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'break_in' => 'datetime',
        'break_out' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function locations()
    {
        return $this->hasMany(TimeLogLocation::class);
    }

    // Method to calculate duration between clock in and clock out
    public function getDurationAttribute()
    {
        if ($this->clock_in && $this->clock_out) {
            return $this->clock_in->diffInMinutes($this->clock_out);
        }
        return 0;
    }

    // Custom method to handle the serialization of DateTime values
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    // Track status changes
    public function getStatusAttribute()
    {
        if ($this->clock_in && !$this->clock_out && !$this->break_in) {
            return 'clocked_in';
        } elseif ($this->clock_in && !$this->clock_out && $this->break_in && !$this->break_out) {
            return 'on_break';
        } elseif ($this->clock_in && !$this->clock_out && $this->break_in && $this->break_out) {
            return 'break_completed';
        } elseif ($this->clock_in && $this->clock_out) {
            return 'done';
        }
        return 'inactive';
    }
    
    // public function timeInLocation()
    // {
    //     return $this->hasOne(Location::class)->where('type', 'time_in');
    // }

    // public function timeOutLocation()
    // {
    //     return $this->hasOne(Location::class)->where('type', 'time_out');
    // }
}
