<?php

namespace App\Exceptions;

use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class AuthenticationSecretEnabledException extends Exception
{
    /**
     * The attempted user.
     *
     * @var \App\User
     */
    private $user;

    /**
     * The authentication token.
     *
     * @var string
     */
    private $token;

    /**
     * The token `expires_at` time.
     *
     * @var \Carbon\Carbon
     */
    private $expiresAt;

    /**
     * Create a new authentication secret enabled exception.
     *
     * @param \App\User $user
     * @param string $token
     * @param \Carbon\Carbon $expiresAt
     * @param string $message
     */
    public function __construct(
        User $user, string $token, Carbon $expiresAt, string $message = 'Authentication secret enabled.'
    ) {
        parent::__construct($message);

        $this->user = $user;
        $this->token = $token;
        $this->expiresAt = $expiresAt;
    }

    /**
     * Get the attempted user.
     *
     * @return \App\User
     */
    public function getUser() : User
    {
        return $this->user;
    }

    /**
     * Get the authentication token.
     *
     * @return string
     */
    public function getToken() : string
    {
        return $this->token;
    }

    /**
     * Get the token `expires_at` time.
     *
     * @return \Carbon\Carbon
     */
    public function getExpiresAt() : Carbon
    {
        return $this->expiresAt;
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function render(Request $request)
    {
        $username = implode(':', [
            'authentication_token',
            $this->getUser()->email,
            Str::orderedUuid()->toString(),
            Str::random(),
        ]);

        Cache::put($username, $this->getToken(), 10 * 60);

        return Response::json([
            'token_type' => 'Multi-factor',
            'expires_in' => 10 * 60,
            'authentication_token' => $username,
        ]);
    }
}
