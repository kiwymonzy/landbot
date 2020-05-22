<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'phone',
        'company',
        'freshsales_id',
    ];

    public function calls()
    {
        return $this->hasMany(Call::class);
    }

    public function spendings()
    {
        return $this->hasMany(Spending::class);
    }

    public function budgetMutations()
    {
        return $this->hasMany(BudgetMutation::class);
    }

    public function statusMutations()
    {
        return $this->hasMany(StatusMutation::class);
    }
}
