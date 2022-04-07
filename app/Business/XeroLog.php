<?php


namespace App\Business;
use HitPay\Business\Ownable;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @package App\Business
 * @property integer $sales_count
 * @property integer $refund_count
 * @property integer $fee_count
 * @property string  $feed_date
 */
class XeroLog extends  Model
{
    use Ownable, UsesUuid, SoftDeletes;

    protected $table = 'business_xero_logs';
}
