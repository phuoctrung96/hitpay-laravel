<?php

namespace App\Business;

use HitPay\Business\Ownable;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Person extends Model
{
    use Ownable, UsesUuid, SoftDeletes;

    protected $table = 'business_persons';

    protected $casts = [
        'relationship' => 'array',
        'data' => 'array'
    ];

    protected $guarded = [];

    const DEFAULT_RELATIONSHIP = [
        'owner' => false,
        'representative' => false,
        'director' => false,
        'executive' => false,
    ];

    /**
     * Get associate payment providers
     * @return MorphToMany
     */
    public function paymentProviders() : MorphToMany
    {
        return $this->morphedByMany(
            PaymentProvider::class,
            'associable',
            'business_associable_persons'
        );
    }

    /**
     * Get associate verifications
     * @return MorphToMany
     */
    public function verifications() : MorphToMany
    {
        return $this->morphedByMany(
            Verification::class,
            'associable',
            'business_associable_persons'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function files() : MorphToMany
    {
        return $this->morphToMany(
            File::class,
            'associable',
            'business_associable_file',
            'associable_id',
            'file_id',
            'id',
            'id'
        );
    }
}
