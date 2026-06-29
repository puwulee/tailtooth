<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipmentCheck extends Model
{
    protected $guarded = [];
    protected $casts = ['passed' => 'boolean'];
}
