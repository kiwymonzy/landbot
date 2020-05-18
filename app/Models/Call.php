<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Call extends Model
{
    protected $fillable = [
        'answered',
        'missed',
        'date_name',
        'date_from',
        'date_to',
    ];

    protected $casts = [
        'answered' => 'integer',
        'missed' => 'integer',
    ];

    protected $dates = [
        'date_from',
        'date_to',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
