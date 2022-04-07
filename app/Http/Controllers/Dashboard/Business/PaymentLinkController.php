<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Business;
use App\Business\Charge;
use App\Enumerations\Business\Channel;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\Business\PaymentRequestStatus;
use App\Enumerations\Business\PluginProvider;
use App\Enumerations\CurrencyCode;
use App\Enumerations\PaymentProvider;
use App\Exceptions\HitPayLogicException;
use App\Helpers\Currency;
use App\Helpers\PaymentLink;
use App\Http\Controllers\Controller;
use App\Http\Resources\Business\Link as LinkResource;
use App\Manager\BusinessManagerInterface;
use App\Manager\PaymentRequestManagerInterface;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Business\Invoice;
use App\Business\PaymentRequest;

/**
 * Class PaymentLinkController
 * @package App\Http\Controllers\Dashboard\Business
 */
class PaymentLinkController extends Controller
{
    /**
     * PaymentLinkController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request, Business $business, BusinessManagerInterface $businessManager)
    {
        Gate::inspect('operate', $business)->authorize();

        $paginator = $business->paymentRequests();

        $status = $request->get('status', PaymentRequestStatus::ALL);
        $status = strtolower($status);

        if ($status == PaymentRequestStatus::ALL) {
            $paginator->whereIn('status', [
                PaymentRequestStatus::PENDING,
                PaymentRequestStatus::COMPLETED,
                PaymentRequestStatus::EXPIRED,
            ]);
        } elseif ($status == PaymentRequestStatus::PENDING) {
            $paginator->where('status', PaymentRequestStatus::PENDING
            );
        } elseif ($status == PaymentRequestStatus::COMPLETED) {
            $paginator->where('status', PaymentRequestStatus::COMPLETED);
        }

        $paginator = $paginator->where('channel', PluginProvider::LINK)->where('is_default', 0)
            ->orderByDesc('id')->paginate(10);

        $paginator->appends('status', $status);

        $currencies = CurrencyCode::listConstants(['ZERO_DECIMAL_CURRENCIES', 'CURRENCY_SYMBOLS']);
        $zero_decimal_cur = CurrencyCode::ZERO_DECIMAL_CURRENCIES;

        return Response::view('dashboard.business.payment-link.index', compact('business', 'paginator', 'status', 'currencies', 'zero_decimal_cur'));
    }


    /**
     * @param Request $request
     * @param Business $business
     * @param PaymentRequestManagerInterface $paymentRequestManager
     * @param BusinessManagerInterface $businessManager
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     * @throws \ReflectionException
     */
    public function store(
        Request $request,
        Business $business,
        PaymentRequestManagerInterface $paymentRequestManager,
        BusinessManagerInterface $businessManager
    )
    {
        Gate::inspect('operate', $business)->authorize();

        $data = $this->validate($request, [
            'id' => [
                'nullable',
                'string',
            ],
            'currency' => [
                'required',
                'string'
            ],
            'amount' => [
                'required',
                'numeric',
                'between:0.01,' . PaymentLink::MAX_AMOUNT
            ],
            'expiry_date' => [
                'nullable',
                'date_format:Y-m-d',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'reference_number' => [
                'nullable',
                'string',
                'max:255',
            ],
            'repeated' => [
                'nullable',
                Rule::in([true, false]),
            ]
        ]);

//        $charge = $this->createCharge($business, $data);

        $apiKey = $business->apiKeys()->first();
        $businessApiKey = $apiKey->api_key;

        $provider = PluginProvider::getProviderByChanel(PluginProvider::LINK);

        if ($paymentMethods = $businessManager->getBusinessProviderPaymentMethods($business, $provider, strtolower($data['currency']))) {
            $paymentMethods = array_flip($paymentMethods);
        } else {
            $paymentMethods = $businessManager->getByBusinessAvailablePaymentMethods($business, strtolower($data['currency']));
        }

        $paymentMethods = array_keys($paymentMethods);

        $data = [
            'email' => $data['email'] ?? null,
            'redirect_url' => null,
            'webhook' => null,
            'currency' => strtolower($data['currency']),
            'purpose' => $data['reference_number'] ?? null,
            'reference_number' => $data['reference_number'] ?? null,
            'amount' => $data['amount'],
            'channel' => PluginProvider::LINK,
            'send_email' => true,
            'allow_repeated_payments' => $data['repeated'] ?? false,
            'expiry_date' => $data['expiry_date'] ?? null
        ];

        $paymentRequest = $paymentRequestManager->create(
            $data,
            $businessApiKey,
            $paymentMethods,
            $platform ?? null
        );

        return new LinkResource($paymentRequest);
    }

    /**
     * @param Request $request
     * @param Business $business
     * @param Invoice $paymentLink
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function delete(Request $request, Business $business, PaymentRequest $paymentRequest)
    {
        Gate::inspect('operate', $business)->authorize();

        $paymentRequest->delete();
        $request->session()->flash('success_message', 'Payment link successfully deleted');

        return Response::redirectToRoute('dashboard.business.payment-links.index', $business);
    }

    private function createCharge(Business $business, $data)
    {

        $charge = new Charge;

        $charge->channel = Channel::LINK_SENT;

        $charge->currency = strtolower($data['currency']);
        $charge->remark = $data['remark'] ?? null;
        $charge->amount = getRealAmountForCurrency($charge->currency, $data['amount'], function (string $currency) {
            throw new HitPayLogicException(sprintf('The currency [%s] is invalid.', $currency));
        });

        $charge->status = ChargeStatus::REQUIRES_PAYMENT_METHOD;

        DB::transaction(function () use ($business, $charge) {
            $business->charges()->save($charge);
        });

        return $charge;
    }
}
