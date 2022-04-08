<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\User\Register\BusinessForm;
use App\Enumerations\CountryCode;
use App\Enumerations\VerificationProvider;
use App\Enumerations\VerificationStatus;
use App\Models\BusinessPartner;
use App\Services\Wati\BusinessOnboarding;
use HitPay\Data\Countries;
use HitPay\Verification\Cognito\FlowSession\Retrieve;
use HitPay\Verification\Cognito\FlowSession\Create;
use Illuminate\Support\Facades;
use App\Business;
use App\Business\BusinessCategory;
use App\Business\HelpGuides;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\Business\OrderStatus;
use App\Http\Controllers\Controller;
use App\Logics\BusinessRepository;
use App\Manager\ApiKeyManager;
use App\Services\XeroApiFactory;
use App\XeroOrganization;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use XeroAPI\XeroPHP\Models\Accounting\Account;
use XeroAPI\XeroPHP\Models\Accounting\Organisation;
use XeroAPI\XeroPHP\Models\Accounting\Organisations;
use HitPay\Stripe\Payout;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;

class BusinessController extends Controller
{
    /**
     * BusinessController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    private static function sortPayouts ($a, $b) {
      return strtotime($b["created_date"]) - strtotime($a["created_date"]);
    }

    /**
     * @param Request $request
     * @param Business $business
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Request $request, Business $business)
    {
        $user = Auth::user();

        $this->authorizeForUser($user, 'view', $business);

        $businessBankAccount = $business->bankAccounts->first();

        if (!$businessBankAccount) {
            if ($business->usingStripeCustomAccount()) {
                Session::flash('error_message', 'Please input your bank account first');

                return Response::redirectToRoute('dashboard.business.settings.bank-accounts.create-page', $business->getKey());
            }
        }

        // Today amounts per currency
        $totalCollectionForThisMonthFromDatabase = $business->charges()
            ->selectRaw('currency, sum(amount) as sum')
            ->where('status', ChargeStatus::SUCCEEDED)
            ->whereDate('closed_at', Date::now()->toDateString())
            ->groupBy('currency')
            ->pluck('sum', 'currency');

        foreach ($totalCollectionForThisMonthFromDatabase as $code => $amount) {
          $totalCollectionForThisMonth[] = [
              'currency' => $code,
              'amount' => number_format(getReadableAmountByCurrency($code, $amount), 2),
          ];
        }

        // Ensure that we always have data, even zero
        if (!$totalCollectionForThisMonthFromDatabase->has('sgd')) {
          $totalCollectionForThisMonth[] = [
            'currency' => 'sgd',
            'amount' => '0.00',
          ];
        }

        if (!$totalCollectionForThisMonthFromDatabase->has('usd')) {
          $totalCollectionForThisMonth[] = [
            'currency' => 'usd',
            'amount' => '0.00',
          ];
        }

        $todayCharges = $business->charges()
          ->where('status', ChargeStatus::SUCCEEDED)
          ->whereDate('closed_at', Date::now()->toDateString())
          ->orderBy('closed_at')
          ->get();

        // Last transactions
        $lastTransactionsDb = $business->charges()
          ->where('status', ChargeStatus::SUCCEEDED)
          ->orderBy('business_id')
          ->orderByDesc('closed_at')
          ->limit(5)
          ->get();

        $lastTransactions = [];

        foreach ($lastTransactionsDb as $t) {
          $lastTransactions[] = [
            'id' => $t->plugin_provider_order_id ?? $t->plugin_provider_reference ?? $t->id,
            'closed_at' => $t->closed_at,
            'customer_email' => $t->customer_email,
            'payment_provider' => $t->payment_provider,
            'payment_provider_charge_method' => $t->payment_provider_charge_method,
            'currency' => $t->currency,
            'amount' => number_format(getReadableAmountByCurrency($t->currency, $t->amount), 2)
          ];
        }

        // Payment methods
        $providersDb = $business->paymentProviders()->get();

        foreach ($providersDb as $p) {
          $providers[] = $p->payment_provider;
        }

        // Payouts
        $payouts = [];

        // PayNow payouts
        // $transfers = $business->transfers()
        //     ->where('payment_provider', PaymentProviderEnum::DBS_SINGAPORE)
        //     ->orderByDesc('created_at')
        //     ->limit(5)
        //     ->get();
        // TODO Fix dashboard SQL perf issue
        $transfers = [];
        foreach ($transfers as $payout) {
          $payouts[] = [
            'type' => 'paynow',
            'amount' => number_format(getReadableAmountByCurrency($payout->currency, $payout->amount), 2),
            'created_date' => $payout->created_at
          ];
        }

        // Stripe payouts
        $stripe = $business->paymentProviders()->where('payment_provider', 'stripe_sg')->first();

        if ($stripe) {
          $stripePayouts = [];
          try {
            $stripePayouts = Payout::new($stripe->payment_provider, $stripe->payment_provider_account_id)->index(5);
          }catch (\Exception $e) {
            // Error with
          }

          foreach ($stripePayouts as $payout) {
            $payouts[] = [
              'type' => 'stripe',
              'amount' => number_format(getReadableAmountByCurrency($payout->currency, $payout->amount), 2),
              'created_date' => Date::createFromTimestamp($payout->created)->toDateString()
            ];
          }
        }

        // Sort payouts and limit by 5
        usort($payouts, array($this, "sortPayouts"));
        $payouts = array_slice($payouts, 0, 5);

        // Resulting data
        $dailyData = array(
          'currencies' => $totalCollectionForThisMonth,
          'transactionsCount' => $todayCharges->count(),
          'lastTransactions' => $lastTransactions,
          'lastPayouts' => $payouts,
          'providers' => $providers ?? null
        );

        $storeName = $business->getName();
        $storeLink = route('shop.business', $business->identifier ?? $business->getKey());
        $isPayment = $business->paymentProviders()
                ->whereIn('payment_provider', [ $business->payment_provider, \App\Enumerations\PaymentProvider::DBS_SINGAPORE ])
                ->count() > 0;

        $verification = $business->verifications()->latest()->first();

        if (Facades\App::environment('local')) {
            // for mock on local
            $cognitoResponseMock = '{"id": "flwses_52xR9LKo77r1Np", "user": {"name": {"last": "Knope", "first": "Leslie"}, "email": "user@example.com", "phone": "+19876543212", "address": {"city": "Pawnee", "street": "123 Main St.", "street2": "Unit 42", "postal_code": "46001", "subdivision": "IN", "country_code": "US"}, "id_number": {"type": "us_ssn", "value": "123456789", "category": "tax_id"}, "ip_address": "192.0.2.42", "date_of_birth": "1975-01-18"}, "_meta": "This API format is not v1.0 and is subject to change.", "steps": {"kyc_check": "active", "screening": "waiting_for_prerequisite", "accept_tos": "success", "kyc_screen": "not_applicable", "risk_check": "waiting_for_prerequisite", "verify_sms": "success", "selfie_check": "waiting_for_prerequisite", "documentary_verification": "waiting_for_prerequisite"}, "status": "success", "template": {"id": "flwtmp_4FrXJvfQU3zGUR", "version": 2}, "kyc_check": {"name": {"summary": "match"}, "phone": {"summary": "match"}, "status": "success", "address": {"type": "residential", "po_box": "yes", "summary": "match"}, "id_number": {"summary": "match"}, "date_of_birth": {"summary": "match"}}, "created_at": "2020-07-24T03:26:02Z", "completed_at": "2020-07-24T03:26:02Z", "screening_id": "scr_52xR9LKo77r1Np", "shareable_url": "https://flow.cognitohq.com/verify/flwtmp_4FrXJvfQU3zGUR?key=e004115db797f7cc3083bff3167cba30644ef630fb46f5b086cde6cc3b86a36f", "customer_reference": "your-db-id-3b24110", "previous_session_id": "flwses_42cF1MNo42r9Xj", "documentary_verification": {"status": "success", "documents": [{"images": {"face": "https://example.cognitohq.com/flow_sessions/flwses_52xR9LKo77r1Np/documents/1/face.jpeg", "cropped_back": "https://example.cognitohq.com/flow_sessions/flwses_52xR9LKo77r1Np/documents/1/cropped_back.jpeg", "cropped_front": "https://example.cognitohq.com/flow_sessions/flwses_52xR9LKo77r1Np/documents/1/cropped_front.jpeg", "original_back": "https://example.cognitohq.com/flow_sessions/flwses_52xR9LKo77r1Np/documents/1/original_back.jpeg", "original_front": "https://example.cognitohq.com/flow_sessions/flwses_52xR9LKo77r1Np/documents/1/orignial_front.jpeg"}, "status": "success", "attempt": 1, "analysis": {"authenticity": "match", "image_quality": "high", "extracted_data": {"name": "match", "date_of_birth": "match", "expiration_date": "not_expired", "issuing_country": "match"}}, "extracted_data": {"category": "drivers_license", "id_number": "AB123456", "expiration_date": "1990-05-29", "issuing_country": "US"}}]}}';
            $responseDecoded = json_decode($cognitoResponseMock, true);

            if ($business->country != CountryCode::SINGAPORE && $verification == null) {
                $verification = $business->verifications()->create([
                    'type' => $business->business_type == 'company' ? 'business' : 'personal',
                    'identification' => $responseDecoded['user']['id_number']['value'],
                    'name' => $responseDecoded['user']['name']['first'] . ' ' . $responseDecoded['user']['name']['last'],
                    'status' => '',
                    'cognitohq_data' => $responseDecoded,
                    'verification_provider' => VerificationProvider::COGNITO,
                    'verification_provider_account_id' => $responseDecoded['id'],
                    'verification_provider_status' => 'success',
                ]);
            }

            if ($business->country != CountryCode::SINGAPORE && $verification->verification_provider_status != 'success') {
                $verification->update([
                    'type' => $business->business_type == 'company' ? 'business' : 'personal',
                    'identification' => $responseDecoded['user']['id_number']['value'],
                    'name' => $responseDecoded['user']['name']['first'] . ' ' . $responseDecoded['user']['name']['last'],
                    'status' => '',
                    'cognitohq_data' => $responseDecoded,
                    'verification_provider' => VerificationProvider::COGNITO,
                    'verification_provider_account_id' => $responseDecoded['id'],
                    'verification_provider_status' => 'success',
                ]);
            }
        }

        $isShowModalVerification = true;
        $isVerificationVerified = false;

        if ($verification !== null) {
            // sg
            if ($business->country == CountryCode::SINGAPORE) {
                if ($verification->isVerified()) {
                    $isShowModalVerification = false;
                    $isVerificationVerified = true;
                }

                if ($verification->status == VerificationStatus::PENDING) {
                    $isShowModalVerification = false;
                }

                if ($verification->status == VerificationStatus::VERIFIED) {
                    $isShowModalVerification = false;
                }
            } else {
                if ($verification->isVerified()) {
                    $isShowModalVerification = false;
                    $isVerificationVerified = true;
                }

                if ($verification->status == VerificationStatus::PENDING) {
                    $isShowModalVerification = false;
                }

                // TODO get step cognito, if user have finish fill data dont show modal cognito flow
            }
        }

        $verificationProvider = [];

        // init checking only for non-sg business
        $isOwner = false;

        if ($isShowModalVerification) {
            if ($business->country !== CountryCode::SINGAPORE) {
                $businessUser = $business->businessUsers();

                $businessUser = $businessUser
                    ->where('user_id', Facades\Auth::id())
                    ->first();

                $isOwner = $businessUser->isOwner();

                if (!$verification) {
                    $cognitoFlow = new Retrieve();
                    $customerSignature = $cognitoFlow->getCustomerSignature($business);

                    $verificationProvider = [
                        'verificationProviderName' => 'cognito',
                        'publishableKey' => Facades\Config::get('services.cognito.publishable_key'),
                        'templateId' => Facades\Config::get('services.cognito.template_id'),
                        'production_ready' => Facades\Config::get('services.cognito.production_ready'),
                        'customerSignature' => $customerSignature,
                    ];
                } else {
                    if ($verification->verification_provider_status != 'success') {
                        $cognitoFlow = new Retrieve();
                        $customerSignature = $cognitoFlow->getCustomerSignature($business);

                        $verificationProvider = [
                            'verificationProviderName' => 'cognito',
                            'publishableKey' => Facades\Config::get('services.cognito.publishable_key'),
                            'templateId' => Facades\Config::get('services.cognito.template_id'),
                            'production_ready' => Facades\Config::get('services.cognito.production_ready'),
                            'customerSignature' => $customerSignature,
                        ];
                    }
                }
            }
        }

        return Response::view('dashboard.business', compact(
            'business',
            'dailyData',
            'storeName',
            'storeLink',
            'isPayment',
            'isShowModalVerification',
            'isVerificationVerified',
            'verificationProvider',
            'isOwner'
        ));
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    public function showBusinessCreationForm(Request $request)
    {
        if (Gate::inspect('store', Business::class)->allowed()) {
            $user = Auth::user();
            $email = $user->email;

            $businessFormData = BusinessForm::withRequest($request)->process();

            $business_categories = $businessFormData['business_categories'];
            $countries = $businessFormData['countries'];
            $selectedCountry = $businessFormData['selected_country'];

            $partnerReferral = $request->session()->get('partner_referral', '');

            return Response::view('dashboard.business.create', compact(
                'email', 'business_categories', 'partnerReferral',
                'countries', 'selectedCountry'
            ));
        }

        return Response::redirectToRoute('dashboard.home');
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function createBusiness(Request $request)
    {
        Gate::inspect('store', Business::class)->authorize();

        $business = BusinessRepository::store($request, Auth::user());

        if(!empty(Auth::user()->xero_data)) {
            ApiKeyManager::create($business);

            $business->saveXeroInfo(
                Auth::user()->xero_data['refreshToken'],
                Auth::user()->xero_data['tenantId'],
                Auth::user()->xero_data['email']
            );

            $business->xero_refresh_token = Auth::user()->xero_data['refreshToken'];
            $business->xero_tenant_id = Auth::user()->xero_data['tenantId'];

            $accountingApi = XeroApiFactory::makeAccountingApi($business);
            try {
                /** @var Organisations $organisations */
                $organisations = $accountingApi->getOrganisations($business->xero_tenant_id)->getOrganisations();
                /** @var Organisation $organisation */
                foreach ($organisations as $organisation) {
                    XeroOrganization::create([
                        'business_id' => $business->id,
                        'short_code' => $organisation->getShortCode(),
                        'name' => $organisation->getName(),
                    ]);
                }
            } catch (\XeroAPI\XeroPHP\ApiException $apiException) {
                Log::error(json_encode([
                    $apiException->getFile().':'.$apiException->getLine(),
                    $apiException->getMessage(),
                    $apiException->getResponseBody(),
                    $apiException->getResponseHeaders(),
                    $apiException->getTrace()
                ]));
            }
        }

        if ($business->phone_number != '') {
            try {
                $onboardWithWati = new BusinessOnboarding($business);

                $onboardWithWati->onboard();
            } catch (\Exception $e) {
                Log::error(json_encode([
                    $e->getFile().':'.$e->getLine(),
                    $e->getMessage(),
                    $e->getTrace()
                ]));
            }
        }

        $nextOnboardFlow = 'dashboard.business.onboard.paynow.create';

        if ($request->wantsJson()) {
            return Response::json([
                'redirect_url' => URL::route($nextOnboardFlow, [
                    $business->getKey(),
                ]),
            ]);
        }

        return Response::redirectToRoute($nextOnboardFlow, [
            $business->getKey(),
        ]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param Business $business
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function showHelpGuide(Request $request)
    {
        $page_type = $request->get('page_type');
        $data = HelpGuides::where('page_type', $page_type)->first();
        return Response::json($data);
    }
}
