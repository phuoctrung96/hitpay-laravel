<?php

namespace App\Models\Business;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property Carbon last_sync_date
 * @property Carbon initial_sync_date
 * @property string access_token
 * @property string refresh_token
 * @property string realm_id
 * @property Carbon access_token_expires_at
 * @property string fee_account_id
 * @property string sales_account_id
 */
class QuickbookIntegration extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public $dates = [
        'last_sync_date',
        'initial_sync_date',
        'access_token_expires_at',
    ];

    public function accessTokenExpired(): bool
    {
        return $this->access_token_expires_at <= now();
    }
}
