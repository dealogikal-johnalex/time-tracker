<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeLog extends Model
{
    protected $fillable = ['user_id', 'clock_in', 'clock_out', 'duration', 'status', 'note', 'latitude', 'longitude', 'address', 'ip_address', 'device'];

    protected $casts = [
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function locations()
    {
        return $this->hasMany(TimeLogLocation::class);
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
