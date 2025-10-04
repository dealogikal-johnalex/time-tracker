<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeLogLocation extends Model
{
    use HasFactory;

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    protected $fillable = [
        'time_log_id',
        'type',
        'latitude',
        'longitude',
        'address',
        'ip_address',
        'device',
    ];

    public function timeLog()
    {
        return $this->belongsTo(TimeLog::class);
    }
}
