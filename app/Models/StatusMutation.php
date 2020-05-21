<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusMutation extends Model
{
    protected $fillable = [
        'campaign',
        'status_old',
        'status_new',
        'date_name',
        'date_revert',
    ];

    protected $dates = [
        'date_revert',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
