<?php

namespace App\Policies;

use App\Enumerations\Permission;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if a user can view another user.
     *
     * @param \App\User $user
     * @param \App\User $target
     *
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function view(User $user, User $target)
    {
        switch (true) {

            case $user->is($target):
            case $user->role && $user->role->hasPermission(Permission::ALL):
                return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine if a user can update another user.
     *
     * @param \App\User $user
     * @param \App\User $target
     *
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function update(User $user, User $target)
    {
        switch (true) {

            case $user->is($target):
            case $user->role && $user->role->hasPermission(Permission::ALL):
                return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine if a user can deactivate another user.
     *
     * @param \App\User $user
     * @param \App\User $target
     *
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function deactivate(User $user, User $target)
    {
        switch (true) {

            case $user->is($target):
            case $user->role && $user->role->hasPermission(Permission::ALL):
                return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine if a user can reactivate another user.
     *
     * @param \App\User $user
     * @param \App\User $target
     *
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function reactivate(User $user, User $target)
    {
        switch (true) {

            case $user->is($target):
            case $user->role && $user->role->hasPermission(Permission::ALL):
                return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine if a user can ban another user.
     *
     * @param \App\User $user
     * @param \App\User $target
     *
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function ban(User $user, User $target)
    {
        switch (true) {

            case $user->role && $user->role->hasPermission(Permission::ALL):
                return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine if a user can unban another user.
     *
     * @param \App\User $user
     * @param \App\User $target
     *
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function unban(User $user, User $target)
    {
        switch (true) {

            case $user->role && $user->role->hasPermission(Permission::ALL):
                return $this->allow();
        }

        return $this->deny();
    }
}
