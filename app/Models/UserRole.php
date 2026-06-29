<?php

namespace App\Models;

use App\Enums\UserRole as RoleEnum;
use Illuminate\Database\Eloquent\Model;

class UserRole extends Model
{
    protected $guarded = [];
    protected $casts = ['role' => RoleEnum::class];
}
