<?php

namespace App\Http\Controllers\MigratedApi;

use App\Business;
use App\Exceptions\HitPayLogicException;
use Illuminate\Http\Request;

class Controller extends \App\Http\Controllers\Controller
{
    /**
     * Get related business.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \App\Business|null
     * @throws \App\Exceptions\HitPayLogicException
     */
    protected function getBusiness(Request $request) : Business
    {
        /**
         * @var \App\User $user
         */
        $user = $request->user();

        $user->load('businessesOwned');

        $businessesOwnedCount = $user->businessesOwned->count();

        if ($businessesOwnedCount > 1) {
            // How can a user has more than 1 business?
            throw new HitPayLogicException($this->generateErrorMessage(1));
        } elseif ($businessesOwnedCount < 1) {
            // How can a user has no business?
            throw new HitPayLogicException($this->generateErrorMessage(2));
        }

        $business = $user->businessesOwned->first();

        if (!$business instanceof Business) {
            // How can a user has businesses owned but the first object returned is not a Business model?
            throw new HitPayLogicException($this->generateErrorMessage(3));
        }

        $business->load('paymentProviders');

        if ($business->paymentProviders->where('payment_provider', 'stripe_sg')->count() < 1) {
            // How can a user login with this API if the payment provider is not found in this business?
            throw new HitPayLogicException($this->generateErrorMessage(4));
        }

        return $business;
    }

    /**
     * Generate error message
     *
     * @param string $code
     *
     * @return string
     */
    protected function generateErrorMessage(string $code)
    {
        return 'Please contact our support. Error: MA-'.str_pad($code, 2, '0');
    }
}
