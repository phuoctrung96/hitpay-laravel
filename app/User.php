<?php

namespace App;

use App\Business\BusinessUser;
use App\Business\Role as BusinessRole;
use App\Enumerations\PaymentProvider;
use App\Exceptions\AuthenticationSecretEnabledException;
use App\Exceptions\ModelRuntimeException;
use App\Models\BusinessPartner;
use App\Notifications\User\LoginNotification;
use App\Notifications\User\ResetPassword as ResetPasswordNotification;
use App\Notifications\User\VerifyEmail as VerifyEmailNotification;
use Carbon\Carbon;
use Exception;
use HitPay\Model\UsesUuid;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\HasApiTokens;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use PragmaRX\Google2FA\Google2FA;
use PragmaRX\Recovery\Recovery;

/**
 * @property mixed id
 * @property ?Role role
 * @property ?BusinessPartner businessPartner
 * @property mixed business
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, Notifiable, SoftDeletes, UsesUuid;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'birth_date' => 'date',
        'password_updated_at' => 'datetime',
        'verified_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'phone_number_verified_at' => 'datetime',
        'banned_at' => 'datetime',
        'xero_data' => 'array',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'is_verified',
        'is_email_verified',
        'is_phone_number_verified',
        'is_authentication_secret_enabled',
        'is_banned',
        'is_deactivated',
        'businessUsersList',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'authentication_secret',
        'remember_token',
        'verified_at',
        'email_verified_at',
        'phone_number_verified_at',
        'banned_at',
        'deleted_at',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'display_name',
        'first_name',
        'last_name',
        'birth_date',
        'gender',
        'email',
        'phone_number',
        'referral_code',
        'password',
        'locale',
        'email_login_enabled',
        'verified_at',
        'email_verified_at',
        'phone_number_verified_at',
        'xero_data'
    ];

    /**
     * The current session of user via web.
     *
     * @var \App\Session
     */
    private $currentSession;

    /**
     * The attributes those can be verified.
     *
     * @var array
     */
    private $verifiableAttributes = [
        'email',
        'phone_number',
    ];

    /**
     * A temp variable to define the method of token request.
     *
     * @var bool
     */
    private $accessTokenViaAuthenticationSecret = false;

    /**
     * A temp variable to define the email address of token request.
     *
     * @var string|null
     */
    private $accessTokenViaEmailAddress;

    /**
     * The managed businesses cache.
     *
     * @var null|\Illuminate\Support\Collection
     */
    private $businessManagedCache;

    /**
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public static function superAdmins()
    {
        return static::query()
            ->whereHas('role', function ($query) {
                return $query
                    ->where('type', 'system')
                    ->where('title', 'Super Administrator');
            })
            ->get();
    }

    /**
     * The "booting" method of the model.
     *
     * @todo: Review logging part
     */
    protected static function boot() : void
    {
        parent::boot();

        static::creating(function (self $model) : void {
            if (!isset($model->attributes['locale'])) {
                $model->setAttribute('locale', App::getLocale());
            }

            if (!isset($model->attributes['email_login_enabled'])) {
                $model->setAttribute('email_login_enabled', true);
            }
        });

        static::saving(function (self $model) : void {
            $dirties = $model->getDirty();

            if (array_key_exists('password', $dirties)) {
                $model->setAttribute('password_updated_at', $model->freshTimestamp());
            }

            foreach ($model->verifiableAttributes as $key) {
                if ($key === 'email') continue; // email_verified_at is set manually

                if (empty($dirties[$key])) {
                    $model->setAttribute($key.'_verified_at', null);
                }
            }
        });

        static::deleting(function (self $model) : void {
            if ($model->getAttribute('role_id')) {
                throw new ModelRuntimeException('You can\'t delete a user with role associated.');
            }
        });

        static::created(function (self $model) : void {
            $createdAt = $model->getAttribute('created_at');

            $model->createLog('general', 'created', $createdAt);

            // The administrator might able to create an business on behalf after verified the business, the email
            // address and phone number, hence, we will need to log the email verified event here too.
            //
            // NOTE: We are not checking if the email or phone number exists for their verification because we already
            // do it in `static::saving()`, it will set the verification to null if none of them presented.

            foreach ($model->verifiableAttributes as $key) {
                if ($verifiedAt = $model->getAttribute($key.'_verified_at')) {
                    $model->createLog('security', $key.'_verified', $verifiedAt, [
                        $key => $model->getAttributeFromArray($key),
                    ]);
                }
            }

            if ($verifiedAt = $model->getAttribute('verified_at')) {
                $model->createLog('security', 'verified', $verifiedAt);
            }

            if ($roleId = $model->getAttributeFromArray('role_id')) {
                if ($role = Role::find($roleId)) {
                    $model->createSystemLog('access_control', 'assigned', $createdAt, $role);
                }
            }
        });

        static::updated(function (self $model) : void {

            // We can use `static::getChanges()` because the changed attributes has been sync to `static::$changes`.

            $changes = $model->getChanges();

            $original = Arr::only($model->getOriginal(), array_keys($changes));

            $updatedAt = $model->getAttribute('updated_at');

            $addedRemovedChangedEvent = function ($key) use ($model, $changes, $original, $updatedAt) {
                if (array_key_exists($key, $changes)) {
                    if (empty($original[$key])) {
                        $model->createLog('security', $key.'_added', $updatedAt, [
                            $key => $changes[$key],
                        ]);
                    } elseif (empty($changes[$key])) {
                        $model->createLog('security', $key.'_removed', $updatedAt, [
                            $key => $original[$key],
                        ]);
                    } else {
                        $model->createLog('security', $key.'_changed', $updatedAt, [
                            $key => [
                                'to' => $changes[$key],
                                'from' => $original[$key],
                            ],
                        ]);
                    }
                }
            };

            // This is to prevent error when the original is missing. This scenario will happen when the model is just
            // created and get updated without refreshing the model.

            if ($model->wasRecentlyCreated) {
                foreach ($changes as $key => $value) {
                    if (!array_key_exists($key, $original)) {
                        $original[$key] = null;
                    }
                }
            }

            /////////////////////////////////////////////////////////////////////////////////////////////////////////
            //                                                                                                     //
            // NOTE: The sequence of the following checking is important, it makes the logs looks more reasonable. //
            //                                                                                                     //
            /////////////////////////////////////////////////////////////////////////////////////////////////////////

            // Due to the 'restored' event is fired after the 'saved' event, the changed attributes are already synced
            // to original and we can't trace the original attributes changed in restoring. Therefore, we will log the
            // 'restored' event here.

            if (array_key_exists('deleted_at', $changes)) {
                if (empty($changes['deleted_at'])) {
                    $model->createLog('general', 'restored', $updatedAt);
                }
            }

            // Here we will filter out and get what are the remaining attributes we will want to log as updated.
            //
            // NOTE: Do not include the attributes used in other events, we don't want to create redundant data.

            $updates = Arr::only($changes, [
                'display_name',
                'first_name',
                'last_name',
                'birth_date',
                'gender',
            ]);

            if (!empty($updates)) {
                foreach ($updates as $key => $value) {
                    $updates[$key] = [
                        'to' => $value,
                        'from' => $original[$key],
                    ];
                }

                $model->createLog('general', 'updated', $updatedAt, $updates);
            }

            $addedRemovedChangedEvent('referral_code');

            if (array_key_exists('password', $changes)) {
                $model->createLog('security', 'password_changed', $model->getAttribute('password_updated_at'));
            }

            if (array_key_exists('authentication_secret', $changes)) {
                if (empty($original['authentication_secret'])) {
                    $model->createLog('security', 'authentication_secret_enabled', $updatedAt);

                    $model->generateRecoveryCodes();
                } elseif (empty($changes['authentication_secret'])) {
                    $model->createLog('security', 'authentication_secret_disabled', $updatedAt);

                    $model->deleteAllRecoveryCodes();
                } else {
                    $model->createLog('security', 'authentication_secret_reset', $updatedAt);
                    $model->regenerateRecoveryCodes();
                }
            }

            // NOTE: We are not checking if the email or phone number exists for their verification because we already
            // do it in `static::saving()`, it will set the verification to null if none of them presented.

            foreach ($model->verifiableAttributes as $key) {
                $addedRemovedChangedEvent($key);

                if (array_key_exists($key.'_verified_at', $changes)) {
                    if (!isset($changes['email'])) continue; // in case email_verified_at was set manually

                    if (empty($original[$key.'_verified_at'])) {
                        $model->createLog('security', $key.'_verified', $model->getAttribute($key.'_verified_at'), [
                            $key => $changes[$key],
                        ]);
                    }
                }
            }

            if (array_key_exists('verified_at', $changes)) {
                if (empty($changes['verified_at'])) {
                    $model->createLog('general', 'unverified', $updatedAt);
                } else {
                    $model->createLog('general', 'verified', $model->getAttribute('verified_at'));
                }
            }

            if (array_key_exists('role_id', $changes)) {
                if (!empty($original['role_id'])) {
                    if ($role = Role::find($original['role_id'])) {
                        $model->createSystemLog('access_control', 'retracted', $updatedAt, $role);
                    }
                }

                if (!empty($changes['role_id'])) {
                    if ($role = Role::find($changes['role_id'])) {
                        $model->createSystemLog('access_control', 'assigned', $updatedAt, $role);
                    }
                }
            }

            if (array_key_exists('banned_at', $changes)) {
                if (empty($changes['banned_at'])) {
                    $model->createLog('general', 'unbanned', $updatedAt);
                } else {
                    $model->createLog('general', 'banned', $updatedAt);
                }
            }
        });

        static::deleted(function (self $model) : void {
            if (empty($model->getOriginal('deleted_at'))) {
                $model->createLog('general', 'trashed', $model->getAttribute('deleted_at'));
            }
        });
    }

    /**
     * Get mutator for "name" attribute.
     *
     * @return string|null
     */
    public function getNameAttribute() : ?string
    {
        return $this->display_name ?? $this->first_name ?? $this->last_name;
    }

    /**
     * Get mutator for "is authentication secret enabled" attribute.
     *
     * @return bool
     */
    public function getIsAuthenticationSecretEnabledAttribute() : bool
    {
        return !is_null($this->authentication_secret);
    }

    /**
     * Get mutator for "is verified" attribute.
     *
     * @return bool
     */
    public function getIsVerifiedAttribute() : bool
    {
        return !is_null($this->verified_at);
    }

    /**
     * Get mutator for "is email verified" attribute.
     *
     * @return bool
     */
    public function getIsEmailVerifiedAttribute() : bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Get mutator for "is phone number verified" attribute.
     *
     * @return bool
     */
    public function getIsPhoneNumberVerifiedAttribute() : bool
    {
        return !is_null($this->phone_number_verified_at);
    }

    /**
     * Get mutator for "is banned" attribute.
     *
     * @return bool
     */
    public function getIsBannedAttribute() : bool
    {
        return !is_null($this->banned_at);
    }

    /**
     * Get mutator for "is deactivated" attribute.
     *
     * @return bool
     */
    public function getIsDeactivatedAttribute() : bool
    {
        return $this->trashed();
    }

    /**
     * Set mutator for "referral_code" attribute.
     *
     * @param string|null $value
     */
    public function setReferralCodeAttribute(?string $value) : void
    {
        if (is_string($value)) {
            $value = strtoupper($value);
        }

        $this->attributes['referral_code'] = $value;
    }

    /**
     * Set mutator for "password" attribute.
     *
     * @param string $value
     */
    public function setPasswordAttribute(string $value) : void
    {
        $this->attributes['password'] = Hash::make($value);
    }

    /**
     * Indicate if authentication secret is enabled.
     *
     * @return bool
     */
    public function isAuthenticationSecretEnabled() : bool
    {
        return $this->is_authentication_secret_enabled;
    }

    /**
     * Indicate if user is banned.
     *
     * @return bool
     */
    public function isBanned() : bool
    {
        return $this->is_banned;
    }

    /**
     * Indicate if user is deactivated.
     *
     * @return bool
     */
    public function isDeactivated() : bool
    {
        return $this->is_deactivated;
    }

    /**
     * Indicate if businesses managed are loaded.
     *
     * @return bool
     */
    public function businessesManagedLoaded() : bool
    {
        return $this->businessManagedCache instanceof Collection;
    }

    /**
     * Load the businesses managed.
     *
     * @return \Illuminate\Support\Collection
     */
    public function businessesManaged() : Collection
    {
        if (!$this->businessManagedCache instanceof Collection) {
            $this->businessManagedCache = new Collection;

            $this->businessRoles()->with('business')->each(function (BusinessRole $role) {
                $this->businessManagedCache->add($role->business);
            });
        }

        return $this->businessManagedCache;
    }

    /**
     * Get the owned businesses.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Business|\App\Business[]
     */
    public function businessesOwned() : HasMany
    {
        return $this->hasMany(Business::class, 'user_id', 'id');
    }

    /**
     * Get the business roles
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Business\Role|\App\Business\Role[]
     */
    public function businessRoles() : BelongsToMany
    {
        return $this->belongsToMany(BusinessRole::class, 'business_assigned_roles', 'user_id', 'business_role_id', 'id',
            'id', 'business_roles');
    }

    /**
     * Get the failed authentications.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\FailedAuthentication|\App\FailedAuthentication[]
     */
    public function failedAuthentications() : HasMany
    {
        return $this->hasMany(FailedAuthentication::class, 'user_id', 'id');
    }

    /**
     * Get the logs.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\UserLog|\App\UserLog[]
     */
    public function logs() : HasMany
    {
        return $this->hasMany(UserLog::class, 'user_id', 'id');
    }

    /**
     * Get the payment cards.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\PaymentCard|\App\PaymentCard[]
     */
    public function paymentCards() : HasMany
    {
        return $this->hasMany(PaymentCard::class, 'user_id', 'id');
    }

    /**
     * Get the recovery codes.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\RecoveryCode|\App\RecoveryCode[]
     */
    public function recoveryCodes() : HasMany
    {
        return $this->hasMany(RecoveryCode::class, 'user_id', 'id');
    }

    /**
     * Get the assigned role.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Role
     */
    public function role() : BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'id', 'role')
            ->withDefault();
    }

    /**
     * Get the sessions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Session|\App\Session[]
     */
    public function sessions() : HasMany
    {
        return $this->hasMany(Session::class, 'user_id', 'id');
    }

    /**
     * Get the system logs.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Log|\App\Log[]
     */
    public function systemLogs() : HasMany
    {
        return $this->hasMany(Log::class, 'related_user_id', 'id');
    }

    /**
     * Get the notifiable's preferred locale for the notification.
     *
     * @return string
     */
    public function preferredLocale() : string
    {
        return $this->locale;
    }

    /**
     * Find for passport.
     *
     * @param string $username
     *
     * @return $this|null
     */
    public function findForPassport(string $username) : ?self
    {
        if (filter_var($username, FILTER_VALIDATE_EMAIL) !== false) {
            $user = $this->where('email', $username)->first();

            if (!$user instanceof User) {
                return $user;
            }

            $user->accessTokenViaEmailAddress = $username;

            return $user;
        }

        $authenticationToken = Cache::get($username);

        if (is_null($authenticationToken)) {
            return null;
        }

        try {
            $value = Crypt::decrypt($authenticationToken);
        } catch (DecryptException $exception) {
            return null;
        }

        $user = $this->find($value['user_id']);

        if (Date::createFromTimeString($value['expires_at'])->isPast()) {
            $user->failedAuthentications()->create([
                'email' => $value['email'],
                'reason' => 'expired_token',
            ]);

            return null;
        }

        if ($user->email !== $value['email']) {
            $user->failedAuthentications()->create([
                'email' => $value['email'],
                'reason' => 'unmatched_email',
            ]);

            return null;
        }

        $user->accessTokenViaAuthenticationSecret = $username;
        $user->accessTokenViaEmailAddress = $value['email'];

        return $user;
    }

    /**
     * Validate for passport password grant.
     *
     * @param string $password
     *
     * @return bool
     * @throws \App\Exceptions\AuthenticationSecretEnabledException
     */
    public function validateForPassportPasswordGrant(string $password) : bool
    {
        if ($this->accessTokenViaAuthenticationSecret === false) {
            if (Hash::check($password, $this->getAuthPassword())) {
                if ($this->isAuthenticationSecretEnabled()) {
                    $this->askForAuthenticationSecret($this->accessTokenViaEmailAddress);
                }

                $this->notify(new LoginNotification);

                return true;
            }

            $this->failedAuthentications()->create([
                'email' => $this->accessTokenViaEmailAddress,
                'reason' => 'incorrect_password',
            ]);

            return false;
        }

        if ($this->verifyAuthenticationSecret($password)
            || (App::environment('local', 'staging','sandbox') && $password === '000000')) {
            Cache::forget($this->accessTokenViaAuthenticationSecret);

            $this->notify(new LoginNotification);
            return true;
        }

        $this->failedAuthentications()->create([
            'email' => $this->accessTokenViaEmailAddress,
            'reason' => 'invalid_authentication_code',
        ]);

        return false;
    }

    /**
     * Send the password reset notification.
     *
     * @param string $token
     */
    public function sendPasswordResetNotification($token) : void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification() : void
    {
        $this->notify(new VerifyEmailNotification);
    }

    /**
     * Create a new session.
     *
     * @param bool $remember
     *
     * @return $this
     */
    public function createSession(bool $remember = false) : self
    {
        $this->currentSession = $this->sessions()->create([
            $this->sessions()->getModel()->getRememberTokenName() => $remember ? Str::random(100) : null,
        ]);

        return $this;
    }

    /**
     * Retrieve an active session, if exists.
     *
     * @param string $value
     *
     * @return $this
     */
    public function retrieveSession(string $value) : self
    {
        $this->currentSession = $this->sessions()->where('value', $value)->first();

        return $this;
    }

    /**
     * Get the current session.
     *
     * @return \App\Session|null
     */
    public function getCurrentSession() : ?Session
    {
        switch (true) {

            case !$this->currentSession instanceof Session:
            case !$this->currentSession->exists:
            case $this->currentSession->isRevoked():
                return null;
        }

        return $this->currentSession;
    }

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string|null
     */
    public function getRememberToken() : ?string
    {
        if ($currentSession = $this->getCurrentSession()) {
            return $currentSession->getRememberToken();
        }

        return null;
    }

    /**
     * Revoke the current session.
     *
     * @return bool
     * @throws \Exception
     */
    public function revokeCurrentSession() : bool
    {
        $currentSession = $this->getCurrentSession();

        $this->currentSession = null;

        if ($currentSession) {
            $currentSession->revoke();
        }

        return true;
    }

    /**
     * Revoke all the sessions.
     *
     * @return bool
     */
    public function revokeAllSessions() : bool
    {
        $this->currentSession = null;

        $this->sessions()->each(function (Session $session) {
            $session->revoke();
        });

        return true;
    }

    /**
     * Revoke the other sessions.
     *
     * @return bool
     */
    public function revokeOtherSessions() : bool
    {
        $otherSessions = $this->sessions();

        if ($currentSession = $this->getCurrentSession()) {
            $otherSessions->whereKeyNot($currentSession->getKey());
        }

        $otherSessions->each(function (Session $session) {
            $session->revoke();
        });

        return true;
    }

    /**
     * Generate new recovery codes. If the new recovery code generated is conflict with any record in database, an
     * exception will be thrown.
     *
     * @param int $quantity
     *
     * @return array
     */
    private function generateRecoveryCodes(int $quantity = 2) : array
    {
        while (!isset($generatedCodes) || count($generatedCodes) < $quantity) {
            $generatedCodes = (new Recovery)->setBlocks(1)->setCount($quantity)->setChars(16)->toArray();
            $generatedCodes = array_unique($generatedCodes);
        }

        $this->recoveryCodes()->insert(array_map(function (string $code) {
            return [
                'user_id' => $this->getKey(),
                'code' => $code,
            ];
        }, $generatedCodes));

        return $this->recoveryCodes()->get()->mapToGroups(function (RecoveryCode $recoveryCode) {
            return [
                $recoveryCode->is_used ? 'used' : 'valid' => $recoveryCode,
            ];
        })->toArray();
    }

    /**
     * Delete all the recovery codes.
     *
     * @return bool
     * @throws \Exception
     */
    private function deleteAllRecoveryCodes() : bool
    {
        $this->recoveryCodes()->delete();

        return true;
    }

    /**
     * Delete all the recovery codes and generate the new set.
     *
     * @return array
     * @throws \Exception
     */
    public function regenerateRecoveryCodes() : array
    {
        if (!$this->exists) {
            throw new Exception('You can\'t regenerate recovery codes for a non-existing user.');
        } elseif ($this->isDeactivated()) {
            throw new ModelRuntimeException('You can\'t regenerate recovery codes for a deactivated user.');
        } elseif (!$this->isAuthenticationSecretEnabled()) {
            throw new ModelRuntimeException('You can\'t regenerate recovery codes for a non-authentication secret enabled user.');
        }

        $this->deleteAllRecoveryCodes();

        $recoveryCodes = $this->generateRecoveryCodes();

        $this->createLog('security', 'recovery_code_regenerated', $this->freshTimestamp());

        return $recoveryCodes;
    }

    /**
     * Verify if the recovery code is valid and mark it as used by default.
     *
     * @param string $code
     * @param bool $checkOnly
     *
     * @return bool
     */
    public function verifyRecoveryCode(string $code, bool $checkOnly = false) : bool
    {
        if ($this->isAuthenticationSecretEnabled()) {
            $recoveryCode = $this->recoveryCodes()->where('code', $code)->first();

            if ($recoveryCode instanceof RecoveryCode && !$recoveryCode->is_used) {
                if ($checkOnly) {
                    return true;
                }

                return $recoveryCode->use();
            }
        }

        return false;
    }

    /**
     * Verify if the given code is matching authentication secret.
     *
     * @param string $code
     *
     * @return bool
     */
    public function verifyAuthenticationSecret(string $code) : bool
    {
        if ($this->isAuthenticationSecretEnabled()) {
            try {
                return (new Google2FA)->verify($code, $this->authentication_secret);
            } catch (IncompatibleWithGoogleAuthenticatorException | InvalidCharactersException | SecretKeyTooShortException $exception) {
                //
            }
        }

        return false;
    }

    /**
     * Ask for authentication secret.
     *
     * @param string|null $email
     *
     * @throws \App\Exceptions\AuthenticationSecretEnabledException
     */
    public function askForAuthenticationSecret(?string $email) : void
    {
        $expiresAt = Date::now()->addMinutes(15);

        throw new AuthenticationSecretEnabledException($this, Crypt::encrypt([
            'user_id' => $this->getKey(),
            'email' => $email,
            'expires_at' => $expiresAt->toDateTimeString(),
        ]), $expiresAt);
    }

    /**
     * Overridden to disable hard deletion on a soft deleted model.
     *
     * @throws \Exception
     */
    public function forceDelete() : void
    {
        throw new Exception('This method has been overridden to disable hard deletion on a soft deleted model.');
    }

    /**
     * Create log for the user's activity.
     *
     * @param string $group
     * @param string $event
     * @param \Illuminate\Support\Carbon $loggedAt
     * @param array|null $attributes
     *
     * @throws \Exception
     */
    protected function createLog(string $group, string $event, Carbon $loggedAt, array $attributes = null) : void
    {
        $log = $this->logs()->make([
            'group' => $group,
            'event' => $event,
            'logged_at' => $loggedAt,
        ]);

        if ($attributes) {
            $log->logAttributes($attributes);
        }

        $log->save();
    }

    /**
     * Create system log for related activity.
     *
     * @param string $group
     * @param string $event
     * @param \Illuminate\Support\Carbon $loggedAt
     * @param \Illuminate\Database\Eloquent\Model $associable
     */
    protected function createSystemLog(string $group, string $event, Carbon $loggedAt, Model $associable) : void
    {
        $this->systemLogs()->make([
            'group' => $group,
            'event' => $event,
            'logged_at' => $loggedAt,
        ])->associable()->associate($associable)->save();
    }

    /**
     * Route notifications for the Firebase channel.
     *
     * @param \Illuminate\Notifications\Notification $notification
     *
     * @return array
     */
    public function routeNotificationForFirebase($notification)
    {
        $accessToken = $this->tokens()->get()->pluck('id')->toArray();

        return FirebaseToken::whereIn('access_token_id', $accessToken)->get()->pluck('registration_token')->toArray();
    }

    public function businesses(): BelongsToMany
    {
        return $this->belongsToMany(Business::class)
            ->withPivot('role_id');
    }

    public function businessUsers(): HasMany
    {
        return $this->hasMany(BusinessUser::class)->whereHas('business');
    }

    public function getBusinessUsersListAttribute(): Collection
    {
        if($this->role->isSuperAdmin() && $business = request()->route()->parameter('business_id')) {
            $ownerRole = Role::owner();

            $businessUser = new BusinessUser();
            $businessUser->user_id = $this->id;
            $businessUser->role_id = $ownerRole->id;
            $businessUser->business_id = $business->id;
            $businessUser->invite_accepted_at = now();

            return collect([$businessUser]);
        }

        return $this->businessUsers;
    }

    public function hasPendingBusinessInvitations(): bool
    {
        return $this->pendingInvitations()->exists();
    }

    public function pendingInvitations(): HasMany
    {
        return $this->businessUsers()
            ->withoutGlobalScope('active')
            ->whereNull('invite_accepted_at');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role && $this->role->isSuperAdmin();
    }

    public function businessPartner(): HasOne
    {
        return $this->hasOne(BusinessPartner::class);
    }

    public function scopeSearch(Builder $builder, string $searchQuery): Builder
    {
        return $builder->where(function ($query) use ($searchQuery) {
            return $query
                ->where('id', 'like', $searchQuery . '%')
                ->orWhere('display_name', 'like', $searchQuery . '%')
                ->orWhere('email', 'like', $searchQuery . '%')
                ->orWhere('phone_number', 'like', $searchQuery . '%')
                ->orWhereHas('businessPartner', function ($query) use($searchQuery) {
                    return $query
                        ->where('referral_code', 'like', $searchQuery . '%');
                });
        });
    }

    public function toBladeModel() : array
    {
        return array_merge($this->only([
            'id',
            'display_name',
            'business_partner',
        ]), [
            'businessUsersList' => $this->businessUsersList->map(function(BusinessUser $businessUser) {
                return $businessUser->only([
                    'user_id',
                    'business_id',
                    'permissions'
                ]);
            })
        ]);
    }
}
