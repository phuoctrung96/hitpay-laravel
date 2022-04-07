<?php

namespace App\Business;

use App\User;
use Exception;
use HitPay\Business\Contracts\Ownable as OwnableContract;
use HitPay\Business\LogHelpers;
use HitPay\Business\Ownable;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class Role extends Model implements OwnableContract
{
    use LogHelpers, Ownable, UsesUuid;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'business_roles';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        //
    ];

    /**
     * The loggable attributes list.
     *
     * @var array
     */
    protected $loggableAttributes = [
        'title',
    ];

    /**
     * The permission cache.
     *
     * @var array
     */
    private $permissionCache = [];

    /**
     * The "booting" method of the model.
     */
    protected static function boot() : void
    {
        parent::boot();

        static::created(function (self $model) : void {
            $model->createLog('access_control', 'created', $model->getAttribute('created_at'),
                Arr::only($model->getAttributes(), $model->loggableAttributes));
        });

        static::updated(function (self $model) : void {

            // We can use `static::getChanges()` because the changed attributes has been sync to `static::$changes`.

            $changes = $model->getChanges();

            $original = Arr::only($model->getOriginal(), array_keys($changes));

            // This is to prevent error when the original is missing. This scenario will happen when the model is just
            // created and get updated without refreshing the model.

            if ($model->wasRecentlyCreated) {
                foreach ($changes as $key => $value) {
                    if (!array_key_exists($key, $original)) {
                        $original[$key] = null;
                    }
                }
            }

            $updates = Arr::only($changes, $model->loggableAttributes);

            if (!empty($updates)) {
                foreach ($updates as $key => $value) {
                    $updates[$key] = [
                        'to' => $value,
                        'from' => $original[$key],
                    ];
                }

                $model->createLog('access_control', 'updated', $model->getAttribute('updated_at'), $updates);
            }
        });

        static::deleting(function (self $model) : void {
            $model->permissions()->delete();

            $now = $model->freshTimestamp();

            $model->users()->each(function (User $user) use ($model, $now) {
                $user->businessRoles()->detach($model);

                $model->createLog('access_control', 'retracted', $now, null, $user);
            });
        });

        static::deleted(function (self $model) : void {
            $model->createLog('access_control', 'deleted', $model->freshTimestamp(),
                Arr::only($model->getOriginal(), $model->loggableAttributes));
        });
    }

    /**
     * Get the users.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\User|\App\User[]
     */
    public function users() : BelongsToMany
    {
        return $this->belongsToMany(User::class, 'business_assigned_roles', 'business_role_id', 'user_id', 'id', 'id',
            'user');
    }

    /**
     * Get the granted permissions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Business\RoleGrantedPermission|\App\Business\RoleGrantedPermission[]
     */
    public function permissions() : HasMany
    {
        return $this->hasMany(RoleGrantedPermission::class, 'business_role_id', 'id');
    }

    /**
     * Grant permissions.
     *
     * @param array $permissions
     *
     * @return array
     * @throws \Exception
     */
    public function grantPermissions(array $permissions) : array
    {
        if (!$this->exists) {
            throw new Exception('You can\'t grant permissions to a non-existing role.');
        }

        $currentPermissions = $this->permissions()->get();

        $desiredPermissions = (new Collection($permissions))->map(function ($permission) {
            return [
                'business_role_id' => $this->getKey(),
                'permission' => $permission,
            ];
        });

        $permissionsToBeRemoved = $currentPermissions
            ->whereNotIn('permission', $desiredPermissions->pluck('permission')->toArray())
            ->pluck('permission')->toArray();

        $permissionsToBeGranted = $desiredPermissions
            ->whereNotIn('permission', $currentPermissions->pluck('permission')->toArray());

        if (count($permissionsToBeRemoved)) {
            $this->permissions()->whereIn('permission', $permissionsToBeRemoved)->delete();

            $doLogging = true;
        }

        if ($permissionsToBeGranted->count()) {
            $this->permissions()->insert($permissionsToBeGranted->toArray());

            $doLogging = true;
        }

        $attributes = [
            'granted' => $permissionsToBeGranted->pluck('permission')->toArray(),
            'removed' => $permissionsToBeRemoved,
        ];

        if ($doLogging ?? false) {
            $this->createLog('access_control', 'permissions_changed', $this->freshTimestamp(),
                array_filter($attributes));
        }

        return $attributes;
    }

    /**
     * Check if the role has given permission.
     *
     * @param array|string $permissions
     * @param bool $strict
     *
     * @return bool
     */
    public function hasPermission($permissions, bool $strict = true) : bool
    {
        if (!$this->relationLoaded('permissions')) {
            $this->load('permissions');

            $this->permissionCache = $this->permissions->pluck('permission')->toArray();
        }

        if (is_string($permissions)) {
            return in_array($permissions, $this->permissionCache);
        } elseif (is_array($permissions)) {
            if ($strict) {
                foreach ($permissions as $permission) {
                    if (!in_array($permission, $this->permissionCache)) {
                        return false;
                    }
                }

                return true;
            }

            foreach ($permissions as $permission) {
                if (in_array($permission, $this->permissionCache)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Attach a user to a role
     *
     * @param \App\User $user
     *
     * @return bool
     * @throws \Exception
     */
    public function attachUser(User $user) : bool
    {
        if (!$this->exists) {
            throw new Exception('You can\'t grant permissions to a non-existing role.');
        }

        $this->users()->attach($user);

        $this->createLog('access_control', 'assigned', $this->freshTimestamp(), null, $user);

        return true;
    }

    /**
     * Detach a user from a role
     *
     * @param \App\User $user
     *
     * @return bool
     * @throws \Exception
     */
    public function detachUser(User $user) : bool
    {
        if (!$this->exists) {
            throw new Exception('You can\'t grant permissions to a non-existing role.');
        }

        $this->users()->detach($user);

        $this->createLog('access_control', 'retracted', $this->freshTimestamp(), null, $user);

        return true;
    }
}
