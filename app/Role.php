<?php

namespace App;

use App\Enumerations\BusinessRole;
use App\Enumerations\RoleType;
use Carbon\Carbon;
use Exception;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * @property mixed id
 * @method static business()
 */
class Role extends Model
{
    use UsesUuid;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'roles';

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

    public static function superAdmin(): self
    {
        return static::query()
            ->where('title', 'Super Administrator')
            ->firstOrFail();
    }

    public static function owner(): self
    {
        return static::query()
            ->where('title', BusinessRole::OWNER)
            ->firstOrFail();
    }

    public static function admin(): self
    {
        return static::query()
            ->where('title', BusinessRole::ADMIN)
            ->firstOrFail();
    }

    public static function manager(): self
    {
        return static::query()
            ->where('title', BusinessRole::MANAGER)
            ->firstOrFail();
    }

    public static function cashier(): self
    {
        return static::query()
            ->where('title', BusinessRole::CASHIER)
            ->firstOrFail();
    }

    /**
     * The "booting" method of the model.
     */
    protected static function boot() : void
    {
        parent::boot();

        static::created(function (self $model) : void {
            $model->createLog('created', $model->getAttribute('created_at'),
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

                $model->createLog('updated', $model->getAttribute('updated_at'), $updates);
            }
        });

        static::deleting(function (self $model) : void {
            $model->permissions()->delete();

            $model->users()->each(function (User $user) {
                $user->role()->dissociate()->save();
            });
        });

        static::deleted(function (self $model) : void {
            $model->createLog('deleted', $model->freshTimestamp(),
                Arr::only($model->getOriginal(), $model->loggableAttributes));
        });
    }

    /**
     * Get the users.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\User|\App\User[]
     */
    public function users() : HasMany
    {
        return $this->hasMany(User::class, 'role_id', 'id');
    }

    /**
     * Get the granted permissions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\RoleGrantedPermission|\App\RoleGrantedPermission[]
     */
    public function permissions() : HasMany
    {
        return $this->hasMany(RoleGrantedPermission::class, 'role_id', 'id');
    }

    /**
     * Get the related system logs.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany|\App\Log|\App\Log[]
     */
    public function logs() : MorphMany
    {
        return $this->morphMany(Log::class, 'log', 'associable_type', 'associable_id', 'id');
    }

    /**
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
                'role_id' => $this->getKey(),
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
            $this->createLog('permissions_changed', $this->freshTimestamp(), array_filter($attributes));
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
     * Create log for the role.
     *
     * @param string $event
     * @param \Illuminate\Support\Carbon $loggedAt
     * @param array|null $attributes
     *
     * @throws \Exception
     */
    protected function createLog(string $event, Carbon $loggedAt, array $attributes = null) : void
    {
        $this->logs()->make([
            'group' => 'access_control',
            'event' => $event,
            'logged_at' => $loggedAt,
        ])->logAttributes($attributes)->save();
    }

    public function scopeBusiness(Builder $builder): Builder
    {
        return $builder->where('type', RoleType::BUSINESS);
    }

    public function scopeForInvitedUsers(Builder $builder): Builder
    {
        return $builder->where('id', '!=', static::owner()->id);
    }

    public function isSuperAdmin(): bool
    {
        return $this->id === static::superAdmin()->id;
    }

    public function isOwner(): bool
    {
        return $this->id === static::owner()->id;
    }

    public function isAdmin(): bool
    {
        return $this->id === static::admin()->id;
    }

    public function isManager(): bool
    {
        return $this->id === static::manager()->id;
    }

    public function isCashier(): bool
    {
        return $this->id === static::cashier()->id;
    }
}
