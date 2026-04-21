<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    public const TYPE_HOLIDAY = 'holiday';

    public const TYPE_WORKDAY = 'workday';

    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date',
    ];
}
