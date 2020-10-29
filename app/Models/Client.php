<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'phone',
        'company',
        'freshsales_id',
        'landbot_id',
    ];

    //? Potentially unused
    public function calls()
    {
        return $this->hasMany(Call::class);
    }

    //? Potentially unused
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

    public function recommendations()
    {
        return $this->hasMany(BudgetRecommendation::class);
    }

    public function notifications()
    {
        return $this->hasMany(WebNotification::class);
    }

    public function statistics()
    {
        return $this->hasMany(Statistic::class);
    }
}
