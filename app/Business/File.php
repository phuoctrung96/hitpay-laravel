<?php

namespace App\Business;

use HitPay\Business\Ownable;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class File extends Model
{
    use Ownable, UsesUuid, SoftDeletes;

    protected $table = 'business_files';

    protected $casts = [
        'data' => 'array'
    ];

    protected $guarded = [];

    /**
     * Get associate payment providers
     * @return MorphToMany
     */
    public function paymentProviders() : MorphToMany
    {
        return $this->morphedByMany(
            PaymentProvider::class,
            'associable',
            'business_associable_file'
        );
    }

    /**
     * Get associate verifications
     * @return MorphToMany
     */
    public function persons() : MorphToMany
    {
        return $this->morphedByMany(
            Person::class,
            'associable',
            'business_associable_file'
        );
    }
}
