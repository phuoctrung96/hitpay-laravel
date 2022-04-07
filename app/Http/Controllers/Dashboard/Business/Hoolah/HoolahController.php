<?php

namespace App\Http\Controllers\Dashboard\Business\Hoolah;

use App\Business;
use App\Business\PaymentProvider;
use App\Business\Transfer;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;
use App\Http\Controllers\Controller;
use App\Jobs\SendExportedTransfers;
use App\Notifications\AlertPayNowAccountChanged;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class HoolahController extends Controller
{
    /**
     * HoolahController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function showHomepage (Business $business) {
      Gate::inspect('view', $business)->authorize();

      $provider = $business->paymentProviders()->where('payment_provider', PaymentProviderEnum::HOOLAH)->first();

      return Response::view('dashboard.business.payment-providers.hoolah', [
        'business' => $business,
        'provider' => $provider,
        'banks_list' => Transfer::$availableBankSwiftCodes
      ]);
    }

    public function setHoolahStatus (
      Business $business,
      Request $request
    ) {
      Gate::inspect('update', $business)->authorize();

      $data = $this->validate($request, [
        'password' => ['required', 'password'],
        'store_name' => ['required'],
        'company_uen' => ['required'],
        'address' => ['required'],
        'postal_code' => ['required'],
        'store_url' => ['required', 'url'],
      ]);  

      unset($data['password']);

      $provider = $business->paymentProviders()->where('payment_provider', PaymentProviderEnum::HOOLAH)->first();

      $isExisting = $provider instanceof PaymentProvider;

      if (!$isExisting) {
        $provider = new PaymentProvider;
        $provider->business_id = $business->id;
        $provider->onboarding_status = 'pending_verification';
        $provider->payment_provider = PaymentProviderEnum::HOOLAH;
        $provider->payment_provider_account_id = Str::uuid()->toString();

        // Hoolah onboarding request
        $client = new Client([
          'base_uri' => 'https://' . config('services.hoolah.onboarding_domain')
        ]);

        $auth_str = config('services.hoolah.username') . ':' . config('services.hoolah.password');
        $auth_str = 'Basic ' . base64_encode($auth_str);

        $data['business_id_passed_hoolah'] = Str::uuid()->toString();

        $res = $client->put('/api/merchants', [ 
          'body' => json_encode([
            'request_reference' => Str::uuid()->toString(),
            'country' => 'Singapore',
            'business_id' => $data['business_id_passed_hoolah'],
            'business_entity_name' => $business->name,
            'business_address' => $data['address'],
            'shop_name' => $data['store_name'],
            'shop_url' => $data['store_url'],
            'legal_emails' => [
              $business->email
            ],
            'technical_contacts' => [
              [
                'email' => $business->email
              ]
            ],
            'ops_contacts' => [
              [
                'email' => $business->email
              ]
            ]
          ]),
          'headers' => [
            'Authorization' => 'Basic ' . base64_encode(config('services.hoolah.username') . ':' . config('services.hoolah.password'))
          ]  
        ]);

        if ($res->getStatusCode() === 200) {
          $res = json_decode((string) $res->getBody());

          // !!!
          // May be Hoolah will return some id which we can use as
          // payment_provider_account_id ? In this case we will not
          // need business_id_passed_hoolah
        } else {
          $error_msg = 'Hoolah onboarding API failed with error: ' . $res->getStatusCode();
          Log::critical($error_msg);
          App::abort(400, $error_msg);      
        } 
      }

      $provider->data = $data;

      if (!$isExisting) {
        $business->paymentProviders()->save($provider);
      } else {
        $provider->save();
      }

      return compact(['business', 'provider']);  
    }

    public function deauthorizeAccount (
      Business $business,
      Request $request
    ) {
      Gate::inspect('update', $business)->authorize();

      $this->validate($request, [
        'password' => [
          'required',
          'password',
        ]
      ]);  

      $provider = $business->paymentProviders()->where('payment_provider', PaymentProviderEnum::HOOLAH)->first();

      $provider->payment_provider = $provider->payment_provider.'_'.microtime(true);
      $provider->save();

      return;
    }
}
