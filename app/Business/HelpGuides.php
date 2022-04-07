<?php

namespace App\Business;

use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;

class HelpGuides extends Model
{
    use UsesUuid;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'business_help_guides';

    protected $casts = [
        'page_type' => 'string',
        'country' => 'string',
        'help_options' => 'string'
    ];
    
    protected $fillable = ['page_type','country', 'help_options'];
}
