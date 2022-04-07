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
        return $this->role->isOwner();
    }

    public function isAdmin(): bool
    {
        return $this->role->isAdmin();
    }

    public function isManager(): bool
    {
        return $this->role->isManager();
    }

    public function isCashier(): bool
    {
        return $this->role->isCashier();
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
