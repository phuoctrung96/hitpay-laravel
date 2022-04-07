<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuickbooksLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'quickbooks_invoice' => 'array',
    ];
}
