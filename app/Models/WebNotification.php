<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebNotification extends Model
{
    protected $fillable = [
        'origin',
        'data',
        'formatted_data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
