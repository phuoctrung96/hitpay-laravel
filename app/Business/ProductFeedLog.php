<?php


namespace App\Business;

use HitPay\Business\Ownable;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ProductFeedLog
 * @package App\Business
 * @property integer $error_count
 * @property integer $success_count
 * @property string $error_msg
 * @property string $feed_date
 * @property integer $business_id
 */
class ProductFeedLog extends Model
{
    use Ownable, UsesUuid;

    protected $table = 'business_product_feed_logs';
}
