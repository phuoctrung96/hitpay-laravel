<?php

namespace App\Business;

use App\Business;
use HitPay\Business\Contracts\Ownable as OwnableContract;
use HitPay\Business\Ownable;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessCategory extends Model implements OwnableContract
{
    use Ownable, UsesUuid;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'merchant_categories';

    protected $fillable = ['category'];

    /**
     * Get the charges.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Business\Charge|\App\Business\Charge[]
     */
    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class, 'merchant_category', 'id');
    }

    public static function getCategoryName($id){
        return BusinessCategory::find($id)->category;
    }

}
