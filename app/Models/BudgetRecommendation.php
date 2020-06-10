<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetRecommendation extends Model
{
    public const PENDING = 1;
    public const ACCEPTED = 2;
    public const DECLINED = 3;

    protected $fillable = [
        'budget',
        'campaign',
        'calls',
        'change',
        'status',
        'account_id',
        'budget_id',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
