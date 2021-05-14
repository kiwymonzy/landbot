<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FreshSalesGoogleAds extends Model
{
    protected $table = 'freshgoogleadslink';

    protected $fillable = [
        'id',
        'code',
    ];
}
