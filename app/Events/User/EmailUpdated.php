<?php

namespace App\Events\User;

use App\User;
use Illuminate\Queue\SerializesModels;

class EmailUpdated
{
    use SerializesModels;

    /**
     * The user.
     *
     * @var \App\User
     */
    public $user;

    /**
     * Create a new event instance.
     *
     * @param \App\User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
