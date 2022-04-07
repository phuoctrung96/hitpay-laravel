<?php

namespace App\Broadcasting;

use App\User;

class UserChannel
{
    /**
     * Authenticate the user's access to the channel.
     *
     * @param \App\User $user
     * @param string $id
     *
     * @return bool
     */
    public function join(User $user, string $id) : bool
    {
        return $user->getKey() === $id;
    }
}
