<?php

namespace App;

use App\Exceptions\ModelNotUpdatableException;
use App\Exceptions\ModelRuntimeException;
use Carbon\Carbon;
use Exception;
use HitPay\Business\Contracts\Ownable as OwnableContract;
use HitPay\Business\LogHelpers;
use HitPay\Business\Ownable;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Date;

class SubscribedFeature extends Model implements OwnableContract
{
    use LogHelpers, Ownable, UsesUuid;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'subscribed_features';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'price' => 'int',
        'active' => 'bool',
        'auto_renew' => 'bool',
        'trial_ends_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'is_valid',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'active',
    ];

    /**
     * Get mutator for "is valid" attribute.
     *
     * @return bool
     */
    public function getIsValidAttribute() : bool
    {
        if (!$this->exists) {
            return false;
        }

        if (!$this->active) {
            return false;
        }

        $now = $this->freshTimestamp();

        if ($now->lte($this->expires_at)) {
            return true;
        }

        $trialEndsAt = $this->trial_ends_at;

        if ($trialEndsAt instanceof Carbon && $now->lte($trialEndsAt)) {
            return true;
        }

        return false;
    }

    /**
     * Indicate if subscribed feature is valid.
     *
     * @return bool
     */
    public function isValid() : bool
    {
        return $this->is_valid;
    }

    /**
     * The "booting" method of the model.
     */
    protected static function boot() : void
    {
        parent::boot();

        static::saving(function (self $model) : void {

            // The `expires_at` date can't be null, which means there's no free feature, but some sort of the features
            // can be renewed with $0.00, or with later `expires_at` value. The `trial_ends_at` can be null, but not
            // updatable and the datetime must be before `expires_at`. We will need to check these attributes here,
            // before saving.
            //
            // Note: we extend one day of these dates if those dates are created after 12pm.

            $dirties = $model->getDirty();

            $expiresAt = $model->getAttribute('expires_at');

            if ($expiresAt instanceof Carbon) {
                if (array_key_exists('expires_at', $dirties)) {
                    if ($model->wasRecentlyCreated && $expiresAt->diffInHours($expiresAt->endOfDay()) < 12) {
                        $expiresAt = $expiresAt->addDay();
                    }

                    $expiresAt = $expiresAt->endOfDay();

                    $model->setAttribute('expires_at', $expiresAt);
                }
            }

            $trialEndsAt = $model->getAttribute('trial_ends_at');

            if ($trialEndsAt instanceof Carbon) {
                if (array_key_exists('trial_ends_at', $dirties)) {
                    if ($model->wasRecentlyCreated && $trialEndsAt->diffInHours($trialEndsAt->endOfDay()) < 12) {
                        $trialEndsAt = $trialEndsAt->addDay();
                    }

                    $trialEndsAt = $trialEndsAt->endOfDay();

                    $model->setAttribute('trial_ends_at', $trialEndsAt);
                }

                if ($trialEndsAt->gt($expiresAt)) {
                    throw new Exception('The attribute `trial_ends_at` can\'t greater than the attribute `expires_at`.');
                }
            }
        });

        static::creating(function (self $model) : void {
            $model->setAttribute('active', true);
        });

        static::created(function (self $model) : void {
            $model->createlog('subscription', 'subscribed', $model->getAttribute('created_at'));
        });

        static::updating(function (self $model) : void {
            $dirties = $model->getDirty();

            if (array_key_exists('feature', $dirties)) {
                throw ModelNotUpdatableException::forAttribute($model, 'feature');
            }

            if (array_key_exists('trial_ends_at', $dirties)) {
                throw ModelNotUpdatableException::forAttribute($model, 'trial_ends_at');
            }

            if (array_key_exists('expires_at', $dirties)) {
                $newExpiresAt = Date::createFromTimeString($dirties['expires_at']);
                $oldExpiresAt = Date::createFromTimeString($model->getOriginal('expires_at'));

                if ($newExpiresAt->lt($oldExpiresAt)) {
                    throw new ModelRuntimeException('The value of new `expires_at` must be greater than the old one.');
                }
            }
        });

        static::updated(function (self $model) : void {

            // We can use `static::getChanges()` because the changed attributes has been sync to `static::$changes`.

            $changes = $model->getChanges();

            $original = Arr::only($model->getOriginal(), array_keys($changes));

            $updatedAt = $model->getAttribute('updated_at');

            // This is to prevent error when the original is missing. This scenario will happen when the model is just
            // created and get updated without refreshing the model.

            if ($model->wasRecentlyCreated) {
                foreach ($changes as $key => $value) {
                    if (!array_key_exists($key, $original)) {
                        $original[$key] = null;
                    }
                }
            }

            $updates = Arr::only($changes, [
                'currency',
                'price',
                'renewal_cycle',
            ]);

            if (!empty($updates)) {
                foreach ($updates as $key => $value) {
                    $updates[$key] = [
                        'to' => $value,
                        'from' => $original[$key],
                    ];
                }

                $model->createlog('subscription', 'updated', $updatedAt, $updates);
            }

            if (array_key_exists('auto_renew', $changes)) {
                if ($changes['auto_renew']) {
                    $model->createlog('subscription', 'auto_renew_enabled', $updatedAt);
                } else {
                    $model->createlog('subscription', 'auto_renew_disabled', $updatedAt);
                }
            }

            if (array_key_exists('active', $changes)) {
                if ($changes['active']) {
                    $model->createlog('subscription', 'reactivated', $updatedAt);
                } else {
                    $model->createlog('subscription', 'deactivated', $updatedAt);
                }
            }

            if (array_key_exists('expires_at', $changes)) {
                $model->createLog('subscription', 'extended', $updatedAt, [
                    'to' => $changes['expires_at'],
                    'from' => $original['expires_at'],
                ]);
            }
        });
    }

    /**
     * Overridden to disable deletion on the model.
     *
     * @throws \Exception
     */
    public function delete() : void
    {
        throw new Exception('This method has been overridden to disable deletion on the model.');
    }
}
