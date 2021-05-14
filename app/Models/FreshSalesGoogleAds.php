<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FreshSalesGoogleAds extends Model
{
    protected $table = 'freshgoogleadslink';

    protected $fillable = [
        'id',
        'account_name',
        'account_manager',
        'industry',
        'mcc_id',
    ];
}
