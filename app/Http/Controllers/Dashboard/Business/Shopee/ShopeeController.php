<?php

namespace App\Http\Controllers\Dashboard\Business\Shopee;

use App\Business;
use App\Business\PaymentProvider;
use App\Business\Transfer;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;
use App\Enumerations\OnboardingStatus;
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
use App\Helpers\Shopee;
use App\Business\BusinessCategory;

class ShopeeController extends Controller
{
    /**
     * ShopeeController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function showHomepage (Business $business) {
      Gate::inspect('view', $business)->authorize();

      $provider = $business->paymentProviders()->where('payment_provider', PaymentProviderEnum::SHOPEE_PAY)->first();

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

      return Response::view('dashboard.business.payment-providers.shopee', [
        'business' => $business,
        'provider' => $provider,
        'uen' => $uen,
        'verification' => $verification,
        'mcc' => $category ? $category->code : '',
        'business_categories' => $business_categories
      ]);
    }

    public function setShopeeStatus (
      Business $business,
      Request $request
    ) {
      Gate::inspect('update', $business)->authorize();

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

      $provider = $business->paymentProviders()->where('payment_provider', PaymentProviderEnum::SHOPEE_PAY)->first();

      $isExisting = $provider instanceof PaymentProvider;

      if (!$isExisting) {
        $provider = new PaymentProvider;
        $provider->business_id = $business->id;
        $provider->payment_provider = PaymentProviderEnum::SHOPEE_PAY;
        $provider->onboarding_status = OnboardingStatus::PENDING_SUBMISSION;
        // MID
        $provider->payment_provider_account_id = Str::uuid()->toString();        
        // SID
        $data['sid'] = Str::uuid()->toString();

      } else {
        // Copy existing SID
        $data['sid'] = $provider->data['sid'];
      }

      $provider->data = $data;

      if ($isExisting) {
        $provider->reported = false;
        $provider->save();
      } else {
        $business->paymentProviders()->save($provider);
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

      $provider = $business->paymentProviders()->where('payment_provider', PaymentProviderEnum::SHOPEE_PAY)->first();

      $provider->payment_provider = $provider->payment_provider.'_'.microtime(true);
      $provider->save();

      return;
    }
}
