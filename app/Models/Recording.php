<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recording extends Model
{
    protected $fillable = [
        'records',
        'type',
        'date_name',
        'date_from',
        'date_to',
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
