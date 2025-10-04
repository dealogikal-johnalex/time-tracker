<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveLog extends Model
{
    protected $fillable = ['user_id','date','type','reason','status'];

    protected $casts = [
        'date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
