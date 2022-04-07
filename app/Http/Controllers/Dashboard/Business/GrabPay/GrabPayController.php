<?php

namespace App\Http\Controllers\Dashboard\Business\GrabPay;

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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use App\Business\BusinessCategory;
use App\Mail\GrabPayHasGrabPay;

class GrabPayController extends Controller
{
    /**
     * GrabPayController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function showHomepage (Business $business) {
      Gate::inspect('view', $business)->authorize();

      $provider = $business->paymentProviders()->where('payment_provider', PaymentProviderEnum::GRABPAY)->first();

      $business_categories = BusinessCategory::all();

      return Response::view('dashboard.business.payment-providers.grabpay', [
        'business' => $business,
        'provider' => $provider,
        'business_categories' => $business_categories
      ]);
    }

    public function setGrabPayStatus (
      Business $business,
      Request $request
    ) {
      Gate::inspect('update', $business)->authorize();

      // Only allow setup/change GrabPay for verified businesses
      if ($business->businessVerified()) {
        $data = $this->validate($request, [
          'password' => ['required', 'password'],
          'company_uen' => ['required'],
          'city' => ['required'],
          'address' => ['required'],
          'merchant_category_code' => ['required'],
          'postal_code' => ['required'],
          'has_grabpay' => ['required', Rule::in(['true', 'false'])]
        ]);

        // convert to normal boolean
        $data['has_grabpay'] = $data['has_grabpay'] === 'true';
  
        unset($data['password']);
  
        $provider = $business->paymentProviders()->where('payment_provider', PaymentProviderEnum::GRABPAY)->first();
  
        $isExisting = $provider instanceof PaymentProvider;
  
        if (!$isExisting) {
          $provider = new PaymentProvider;
          $provider->business_id = $business->id;
          $provider->payment_provider = PaymentProviderEnum::GRABPAY;
          // Do not send real business_id, use new one
          $provider->payment_provider_account_id = Str::uuid()->toString();        
        } else {
          // Can not update if already submitted
          if ($provider->onboarding_status !== OnboardingStatus::PENDING_SUBMISSION && $provider->onboarding_status !== OnboardingStatus::REJECTED) {
            App::abort(400);
          }
        }
  
        $provider->onboarding_status = OnboardingStatus::PENDING_SUBMISSION;
        $provider->data = $data;
  
        if (!$isExisting) {
          $business->paymentProviders()->save($provider);
        } else {
          $provider->save();
        }

        if ($data['has_grabpay']) {
          Mail::to($business->email)->send(new GrabPayHasGrabPay());
        }        
  
        return compact(['business', 'provider']);    
      } else {
        App::abort(400, 'GrabPay is not available for individual sellers. Please check your account verification.');
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

      $provider = $business->paymentProviders()->where('payment_provider', PaymentProviderEnum::GRABPAY)->first();

      $provider->payment_provider = $provider->payment_provider.'_'.microtime(true);
      $provider->save();

      return;
    }
}
