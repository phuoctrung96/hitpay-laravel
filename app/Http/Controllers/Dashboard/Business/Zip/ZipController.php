<?php

namespace App\Http\Controllers\Dashboard\Business\Zip;

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
use App\Business\BusinessCategory;

class ZipController extends Controller
{
    /**
     * ZipController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function showHomepage (Business $business) {
      Gate::inspect('view', $business)->authorize();

      $provider = $business->paymentProviders()->where('payment_provider', PaymentProviderEnum::ZIP)->first();

      if (!$provider) {
        // this data only needed on first setup
        $paynowProvider = $business->paymentProviders()->where('payment_provider', PaymentProviderEnum::DBS_SINGAPORE)->first();
        $uen = $paynowProvider->data['company']['uen'];
        $category = $business->merchantCategory()->first();

        $verification = $business->verifications()->first();  
      } else {
        $verification = null;
        $uen = '';
        $category = null;
      }

      $business_categories = BusinessCategory::all();      

      return Response::view('dashboard.business.payment-providers.zip', [
        'business' => $business,
        'provider' => $provider,
        'uen' => $uen,
        'verification' => $verification,
        'mcc' => $category ? $category->code : '',
        'business_categories' => $business_categories
      ]);
    }

    public function setZipStatus (
      Business $business,
      Request $request
    ) {
      Gate::inspect('update', $business)->authorize();

      // Only allow setup/change Zip for verified businesses
      if ($business->businessVerified()) {
        $data = $this->validate($request, [
          'password' => ['required', 'password'],
          'store_name' => ['required'],
          'company_uen' => ['required'],
          'city' => ['required'],
          'address' => ['required'],
          'postal_code' => ['required'],
          'mcc' => 'required|regex:/^[0-9]{4}$/'
        ], [
          'mcc.regex' => 'The MCC field should be a four digit number'
        ]);  

        unset($data['password']);

        $provider = $business->paymentProviders()->where('payment_provider', PaymentProviderEnum::ZIP)->first();

        $isExisting = $provider instanceof PaymentProvider;

        if (!$isExisting) {
          $provider = new PaymentProvider;
          $provider->business_id = $business->id;
          $provider->payment_provider = PaymentProviderEnum::ZIP;
          $provider->payment_provider_account_id = '';
          $provider->onboarding_status = 'success';
        }

        $provider->data = $data;

        if ($isExisting) {
          $provider->reported = false;
          $provider->save();
        } else {
          $business->paymentProviders()->save($provider);
        }

        return compact(['business', 'provider']);
      } else {
        App::abort(400, 'Zip is not available for individual sellers. Please check your account verification.');
      }
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

      $provider = $business->paymentProviders()->where('payment_provider', PaymentProviderEnum::ZIP)->first();

      $provider->payment_provider = $provider->payment_provider.'_'.microtime(true);
      $provider->save();

      return;
    }
}
