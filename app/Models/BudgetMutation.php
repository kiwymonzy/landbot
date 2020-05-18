<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetMutation extends Model
{
    protected $fillable = [
        'amount_old',
        'amount_adjust',
        'amount_new',
        'campaign',
        'date_revert',
    ];

    protected $casts = [
        'amount_old' => 'integer',
        'amount_adjust' => 'integer',
        'amount_new' => 'integer',
    ];

    protected $dates = [
        'revert',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
