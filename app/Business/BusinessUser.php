<?php

namespace App\Business;

use App\Business;
use App\Services\BusinessUserPermissionsService;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property \App\Role role
 * @property bool canManageUsers
 * @property User user
 */
class BusinessUser extends Model
{
    protected $guarded = [];

    protected $_role = [];

    protected $table = 'business_user';

    protected $appends = [
        'permissions',
    ];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('active', function (Builder $builder) {
            return $builder->whereNotNull('invite_accepted_at');
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(\App\Role::class);
    }

    public function isOwner(): bool
    {
        if (!array_key_exists('owner', $this->_role)) {
            $this->_role['owner'] = $this->role->isOwner();
        }
        return $this->_role['owner'];
    }

    public function isAdmin(): bool
    {
        if (!array_key_exists('admin', $this->_role)) {
            $this->_role['admin'] = $this->role->isAdmin();
        }
        return $this->_role['admin'];
    }

    public function isManager(): bool
    {
        if (!array_key_exists('manager', $this->_role)) {
            $this->_role['manager'] = $this->role->isManager();
        }
        return $this->_role['manager'];
    }

    public function isCashier(): bool
    {
        if (!array_key_exists('cashier', $this->_role)) {
            $this->_role['cashier'] = $this->role->isCashier();
        }
        return $this->_role['cashier'];
    }

    public function getPermissionsAttribute(): array
    {
        return resolve(BusinessUserPermissionsService::class)->get($this);
    }

    /**
     * @return User
     */
    public function getUserOwner() : User
    {
        $roleOwner = \App\Role::owner();

        return $this->where('role_id', $roleOwner->id)
            ->where('business_id', $this->business_id)
            ->first()->user;
    }
}
