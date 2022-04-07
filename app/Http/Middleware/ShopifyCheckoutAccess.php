<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\ClientRepository;
use App\Business;
use App\Client;

class ShopifyCheckoutAccess
{
    private $client;

    public function __construct(ClientRepository $client)
    {
        $this->client = $client;
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
        if ($request->exists('x_signature')) {
            if (!$this->isValidShopifySignature($request)) {
                App::abort(401, 'Invalid signature.');
            }    
        }        

        return $next($request);
    }

    private function isValidShopifySignature($request)
    {
        $client  = $this->client->find($request->get('x_account_id'));

        if (!$client instanceof Client) {
            Log::critical(sprintf('[CHECKOUT]: Client ID not found %s, %s', $request->get('x_account_id'), $request->headers->get('referer')));

            App::abort(404, 'Invalid client.');
        }

        $business = $client->business()->first();

        if ($this->isValidRequest($request, $client->secret)) {
            $request->attributes->set('api_key'     , $client->secret);
            $request->attributes->set('business_id' , $business->getKey());
            $request->attributes->set('x_timestamp' , gmdate("Y-m-d\TH:i:s\Z"));

            return true;
        }  

        return false;
    }

    private function isValidRequest($request, $secret) 
    {
        $hmacSource = [];
        $signature  = $request->except(['x_signature']);

        unset($signature['x_signature']);

        ksort($signature);

        foreach ($signature as $key => $val) {
            $hmacSource[] = "{$key}{$val}";
        }    

        $sig            = implode("", $hmacSource);
        $calculatedHmac = hash_hmac('sha256', $sig, $secret);  

        return hash_equals($request->input('x_signature'), $calculatedHmac);
    }
}
