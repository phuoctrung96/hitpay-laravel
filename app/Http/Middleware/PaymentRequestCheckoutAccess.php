<?php

namespace App\Http\Middleware;

use Closure;
use App\Business\Charge;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\ClientRepository;
use App\Business;
use App\Client;
use App\Manager\ChargeManagerInterface;
use App\Enumerations\Business\PluginProvider;
use App\Enumerations\Business\PaymentRequestStatus;
use Carbon\Carbon;

class PaymentRequestCheckoutAccess
{
    private $chargeManager;

    public function __construct(ChargeManagerInterface $chargeManager)
    {
        $this->chargeManager = $chargeManager;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $parameters     = $request->route()->parameters();        
        $business       = $request->route()->parameter('business_slug');

        if (isset($parameters['payment_request_id']) && $business instanceof Business) {
            $paymentRequest = $request->route()->parameter('payment_request_id');

            if ($business->getKey() != $paymentRequest->business_id) {
                App::abort(401, 'Invalid business payment request.');
            }   
        }        

        return $next($request);
    }
}