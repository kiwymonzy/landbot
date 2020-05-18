<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Spending extends Model
{
    protected $fillable = [
        'amount',
        'date_name',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
