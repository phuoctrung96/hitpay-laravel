<?php

namespace App\Models\Business;

use HitPay\Business\Ownable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SpecialPrivilege extends Model
{
    use Ownable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'business_special_privileges';

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'granted_at';

    /**
     * The name of the "updated at" column.
     *
     * @var null
     */
    const UPDATED_AT = null;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    const TRANSFER_CSV_WITH_CHARGE_ID = 'transfer:csv+charge_id';

    const TRANSFER_PAUSED = 'transfer_paused';

    /**
     * Set the keys for a save update query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function setKeysForSaveQuery(Builder $query) : Builder
    {
        return $query->where([
            $query->qualifyColumn('business_id') => $this->business_id,
            $query->qualifyColumn('special_privilege') => $this->special_privilege,
        ]);
    }
}
