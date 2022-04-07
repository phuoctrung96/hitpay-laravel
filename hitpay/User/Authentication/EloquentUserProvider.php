<?php

namespace HitPay\User\Authentication;

use App\Exceptions\AuthenticationSecretEnabledException;
use App\User;
use Illuminate\Auth\EloquentUserProvider as UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;

class EloquentUserProvider extends UserProvider
{
    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param string $identifier
     * @param string $token
     *
     * @return \App\User|\Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($identifier, $token) : ?Authenticatable
    {
        $user = $this->retrieveById($identifier);

        if (!$user) {
            return null;
        }

        if ($user instanceof User) {
            $token = json_decode($token, true);

            if ($token === false || !isset($token['value'], $token['token'])) {
                return null;
            }

            $user->retrieveSession($token['value']);

            $token = $token['token'];
        }

        $rememberToken = $user->getRememberToken();

        if ($rememberToken && hash_equals($rememberToken, $token)) {
            return $user;
        }

        return null;
    }

    /**
     * If user is not using extended authenticatable contract, update the "remember me" token in storage.
     *
     * @param \App\User|\Illuminate\Contracts\Auth\Authenticatable $user
     * @param string $token
     */
    public function updateRememberToken(Authenticatable $user, $token) : void
    {
        if ($user instanceof User) {
            return;
        }

        parent::updateRememberToken($user, $token);
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param array $credentials
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        if (empty($credentials)) {
            return null;
        }

        /** @var \App\User|\Illuminate\Contracts\Auth\Authenticatable $query */
        $query = $this->newModelQuery();

        $credentialCount = count($credentials);

        if ($credentialCount === 2 && array_key_exists('auth_token', $credentials)
            && (array_key_exists('auth_code', $credentials) || array_key_exists('recovery_code', $credentials))) {

            try {
                $value = Crypt::decrypt($credentials['auth_token']);
            } catch (DecryptException $exception) {
                return null;
            }

            if (Date::createFromTimeString($value['expires_at'])->isPast()) {
                return null;
            }

            return $query->find($value['user_id']);
        }

        if ($credentialCount === 1 && array_key_exists('password', $credentials)) {
            return null;
        }

        foreach ($credentials as $key => $value) {
            if (Str::contains($key, 'password')) {
                continue;
            }

            if (is_array($value) || $value instanceof Arrayable) {
                $query->whereIn($key, $value);
            } else {
                $query->where($key, $value);
            }
        }

        return $query->first();
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @param array $credentials
     *
     * @return bool
     * @throws \App\Exceptions\AuthenticationSecretEnabledException
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        if (!$user instanceof User) {
            if (isset($credentials['password'])) {
                return $this->hasher->check($credentials['password'], $user->getAuthPassword());
            }

            return false;
        }

        if (isset($credentials['password'])) {
            if ($this->hasher->check($credentials['password'], $user->getAuthPassword())) {
                if ($user->isAuthenticationSecretEnabled()) {
                    $user->askForAuthenticationSecret($credentials['email'] ?? null);
                }

                return true;
            }

            $errorCode = 'incorrect_password';
        } elseif (isset($credentials['auth_code'])) {
            if ($user->verifyAuthenticationSecret($credentials['auth_code'])) {
                return true;
            }

            $errorCode = 'invalid_authentication_code';
        } elseif (isset($credentials['recovery_code'])) {
            if ($user->verifyRecoveryCode($credentials['recovery_code'])) {
                return true;
            }

            $errorCode = 'invalid_recovery_code';
        }

        $user->failedAuthentications()->create([
            'email' => $credentials['email'] ?? null,
            'reason' => $errorCode ?? 'invalid_request',
        ]);

        return false;
    }
}
