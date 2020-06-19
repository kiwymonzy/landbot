<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Statistic extends Model
{
    protected $fillable = [
        'spendings',
        'cost_per_call',
        'click_to_call',
        'clicks',
        'answered',
        'missed',
        'date_name',
        'date_from',
        'date_to',
    ];

    protected $casts = [
        'spendings' => 'float',
        'cost_per_call' => 'float',
        'click_to_call' => 'float',
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
