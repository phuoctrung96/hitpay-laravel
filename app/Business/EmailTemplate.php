<?php

namespace App\Business;

use HitPay\Business\Ownable;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use Ownable, UsesUuid;

    protected $table = 'business_email_templates';

    protected $casts = [
        'common_customisation' => 'array',
        'order_confirmation_template' => 'array',
        'payment_receipt_template' => 'array',
        'invoice_receipt_template' => 'array',
        'mobile_printer_template' => 'array',
        'recurring_invoice_template' => 'array',
        'action_button_text' => 'array',
        'action_button_text_color' => 'array',
        'action_button_background_color' => 'array',
    ];

    protected $guarded = [
        //
    ];
}
