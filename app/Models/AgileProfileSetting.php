<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgileProfileSetting extends Model
{
    protected $primaryKey = 'profileid';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'profileid',
        'client_segments',
        'client_access_mode',
        'app_modules',
    ];

    protected $casts = [
        'client_segments' => 'array',
        'app_modules' => 'array',
    ];
}
