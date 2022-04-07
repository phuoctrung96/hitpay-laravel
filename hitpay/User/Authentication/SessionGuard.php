<?php

namespace HitPay\User\Authentication;

use App\User;
use Illuminate\Auth\Events\CurrentDeviceLogout;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\SessionGuard as Guard;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Support\Str;

class SessionGuard extends Guard
{
    /**
     * The prefix of the recaller.
     *
     * @var string
     */
    protected $recallerPrefix = 'hitpay:';

    /**
     * Get the currently authenticated user.
     *
     * @return \App\User|\Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user() : ?AuthenticatableContract
    {
        if ($this->loggedOut) {
            return null;
        } elseif (!is_null($this->user)) {
            return $this->user;
        }

        $userId = $this->session->get($this->getName());

        // The original method will try to load the user using the identifier in the session if one exists. However, if
        // the user is an instance of App\User, we will need to load the manageable session from database. Will only
        // assign the user if the session is valid. Otherwise we will check for a "remember me" cookie in this request,
        // and if one exists, attempt to retrieve the user using that.

        if (!is_null($userId) && $user = $this->provider->retrieveById($userId)) {
            if ($user instanceof User) {
                $sessionId = $this->session->get($this->getActiveSessionName());

                if ($sessionId && $user->retrieveSession($sessionId)->getCurrentSession()) {
                    $this->user = $user;
                    $this->fireAuthenticatedEvent($this->user);
                }
            } elseif ($this->user = $user) {
                $this->fireAuthenticatedEvent($this->user);
            }
        }

        if (is_null($this->user) && !is_null($recaller = $this->recaller())) {
            $this->user = $this->userFromRecaller($recaller);

            if ($this->user) {
                $this->updateSession($this->user->getAuthIdentifier());

                if ($this->user instanceof User) {
                    $this->session->put($this->getActiveSessionName(), $this->user->getCurrentSession()->value);
                }

                $this->fireLoginEvent($this->user, true);
            }
        }

        return $this->user;
    }

    /**
     * Pull a user from the repository by its "remember me" cookie token.
     *
     * @param \Illuminate\Auth\Recaller $recaller
     *
     * @return \App\User|\Illuminate\Contracts\Auth\Authenticatable|null
     */
    protected function userFromRecaller($recaller) : ?AuthenticatableContract
    {
        if (!$recaller->valid() || $this->recallAttempted) {
            return null;
        }

        $this->recallAttempted = true;

        $authId = $recaller->id();

        // The framework user from recaller method will only pass in the recaller token to retrieve authenticatable.
        // However, if the recaller is identified for App\User, we will JSON encode both the token and value before
        // pass to user provider. The custom user provider will JSON decode it again later once App\User is found.

        if (Str::startsWith($authId, $this->recallerPrefix)) {
            $user = $this->provider->retrieveByToken(str_replace($this->recallerPrefix, '', $authId), json_encode([
                'token' => $recaller->token(),
                'value' => $recaller->hash(),
            ]));
        } else {
            $user = $this->provider->retrieveByToken($recaller->id(), $recaller->token());
        }

        $this->viaRemember = !is_null($user);

        return $user;
    }

    /**
     * Override this method to disable authenticate using HTTP Basic Auth.
     *
     * @param string $field
     * @param array $extraConditions
     */
    public function basic($field = 'email', $extraConditions = []) : void
    {
        // TODO - 2019-12-15
        // Because we are providing two factor authentication method, we will temporarily disable the basic
        // authentication method by keep throwing failed response.

        $this->failedBasicResponse();
    }

    /**
     * Log a user into the application.
     *
     * @param \App\User|\Illuminate\Contracts\Auth\Authenticatable $user
     * @param bool $remember
     */
    public function login(AuthenticatableContract $user, $remember = false) : void
    {
        // The framework login method will only add the user ID in session and create a recaller if remember is true.
        // However, if the user is instance of App\User, a session model will be created at the same time, it will still
        // work the same, but that session model can be managed.

        if ($user instanceof User) {
            $currentSession = $user->createSession($remember)->getCurrentSession();

            $this->updateSession($user->getAuthIdentifier());
            $this->session->put($this->getActiveSessionName(), $currentSession->value);

            if ($remember) {
                $this->getCookieJar()->queue($this->createRecaller(implode('|', [
                    $this->recallerPrefix.$user->getAuthIdentifier(),
                    $currentSession->getRememberToken(),
                    $currentSession->value,
                ])));
            }
        } else {
            $this->updateSession($user->getAuthIdentifier());

            if ($remember) {
                $this->ensureRememberTokenIsSet($user);
                $this->queueRecallerCookie($user);
            }
        }

        $this->fireLoginEvent($user, $remember);
        $this->setUser($user);
    }

    /**
     * Log the user out of the application.
     *
     * @throws \Exception
     */
    public function logout() : void
    {
        $user = $this->user();

        $this->clearUserDataFromStorage();

        if (!is_null($this->user)) {

            // The framework logout method will recycle the remember token (reset), the other active sessions with the
            // old remember token will still active, until the session is expired. However, if the user is instance of
            // App\User, the other sessions will be revoked immediately.

            if ($user instanceof User) {
                $this->session->remove($this->getActiveSessionName());

                $user->revokeAllSessions();
            } elseif (!empty($user->getRememberToken())) {
                $this->cycleRememberToken($user);
            }
        }

        if (isset($this->events)) {
            $this->events->dispatch(new Logout($this->name, $user));
        }

        $this->user = null;
        $this->loggedOut = true;
    }

    /**
     * Log the user out of the application on their current device only.
     *
     * @throws \Exception
     */
    public function logoutCurrentDevice() : void
    {
        $user = $this->user();

        $this->clearUserDataFromStorage();

        // The framework logout current device method will just remove the user's session and set the recaller cookie
        // expire. If the user is instance of App\User, we will remove the another session set to recognize the valid
        // session, and then revoke current session.

        if ($user instanceof User) {
            $this->session->remove($this->getActiveSessionName());

            $user->revokeCurrentSession();
        }

        if (isset($this->events)) {
            $this->events->dispatch(new CurrentDeviceLogout($this->name, $user));
        }

        $this->user = null;
        $this->loggedOut = true;
    }

    /**
     * Invalidate other sessions for the current user.
     *
     * The application must be using the AuthenticateSession middleware for user can't manage session.
     *
     * @param string $password
     * @param string $attribute
     *
     * @return bool|null
     */
    public function logoutOtherDevices($password, $attribute = 'password') : ?bool
    {
        $user = $this->user();

        if (is_null($user)) {
            return null;
        }

        // The framework logout other devices method will rehash the user password and update the recaller cookie, then
        // the AuthenticateMiddleware will invalidate the other session with old recaller. However, if the user is
        // instance of App\User, revoke only the other sessions will do.

        if ($user instanceof User) {
            $result = $user->revokeOtherSessions();
        } else {
            $user->forceFill([
                $attribute => $password,
            ]);

            $result = $user->save();

            if ($this->recaller() || $this->getCookieJar()->hasQueued($this->getRecallerName())) {
                $this->queueRecallerCookie($user);
            }
        }

        $this->fireOtherDeviceLogoutEvent($user);

        return $result;
    }

    /**
     * Get a unique identifier for the auth session key.
     *
     * @return string
     */
    public function getActiveSessionName() : string
    {
        return $this->getName().'_active';
    }
}
