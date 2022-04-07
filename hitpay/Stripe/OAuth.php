<?php

namespace HitPay\Stripe;

use Stripe\Account;
use Stripe\OAuth as StripeOAuth;

class OAuth extends Core
{
    /**
     * Get authorize URL.
     *
     * @param string $platform
     * @param string $state
     *
     * @return string
     */
    public function getAuthorizeUrl(string $redirectUri, string $state, bool $login = false) : string
    {
        return StripeOAuth::authorizeUrl([
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'state' => $state,
            'scope' => 'read_write',
            'stripe_landing' => $login ? 'login' : 'register',
        ]);
    }

    /**
     * Authorize a Stripe account
     *
     * @param string $code
     *
     * @return array
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Stripe\Exception\OAuth\OAuthErrorException
     */
    public function authorizeAccount(string $code) : array
    {
        $token = StripeOAuth::token([
            'grant_type' => 'authorization_code',
            'code' => $code,
        ]);

        return [
            $token,
            Account::retrieve($token->stripe_user_id),
        ];
    }

    /**
     * Deauthorize an account.
     *
     * @param string $code
     * @param string $state
     *
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Stripe\Exception\OAuth\OAuthErrorException
     */
    public function deauthorizeAccount(string $accountId)
    {
        return StripeOAuth::deauthorize([
            'client_id' => $this->configurations['client_id'],
            'stripe_user_id' => $accountId,
        ]);
    }
}
