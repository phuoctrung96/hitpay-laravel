<?php

namespace App\Http\Controllers\Admin;

use App\Business;
use App\Business\PaymentProvider as BusinessPaymentProvider;
use App\Enumerations\PaymentProvider;
use App\Http\Controllers\Controller;
use App\Manager\BusinessManagerInterface;
use App\Providers\AppServiceProvider;
use App\StripeTerminal;
use Exception;
use Illuminate\Database\Connection;
use Illuminate\Http;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Stripe\Exception\InvalidRequestException;
use Stripe\Stripe;
use Stripe\Terminal\Reader;

class BusinessTerminalController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role');
    }

    public function index(Business $business)
    {
        $paginator = $business->stripeTerminals()->orderByDesc('id')->paginate();

        return Response::view('admin.business.terminal-index', compact('business', 'paginator'));
    }

    /**
     * Show page to add new Stripe Terminal.
     *
     * @param  \App\Business  $business
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Business $business) : Http\Response
    {
        $doesntHaveStripePaymentProviders = $business->paymentProviders()->whereIn('payment_provider', [
            PaymentProvider::STRIPE_SINGAPORE,
            PaymentProvider::STRIPE_US,
        ])->doesntExist();

        if ($doesntHaveStripePaymentProviders) {
            App::abort(403, "The business hasn't setup any Stripe under Singapore or United States platform.");
        }

        return Response::view('admin.business.terminal-create', compact('business'));
    }

    /**
     * Store a new Stripe Terminal.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Business  $business
     * @param  \App\Manager\BusinessManagerInterface  $businessManager
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Stripe\Exception\InvalidRequestException
     * @throws \Throwable
     */
    public function store(
        Http\Request $request,
        Business $business,
        BusinessManagerInterface $businessManager
    ) : Http\RedirectResponse
    {
        $stripePaymentProviders = $business->paymentProviders()->whereIn('payment_provider', [
            PaymentProvider::STRIPE_SINGAPORE,
            PaymentProvider::STRIPE_US,
        ])->get();

        if ($stripePaymentProviders->isEmpty()) {
            return Response::redirectToRoute('admin.business.terminal.create', $business->getKey())->with([
                'error_message' => "The business hasn't setup any Stripe under Singapore or United States platform.",
            ]);
        } elseif ($stripePaymentProviders->count() > 1) {
            throw new Exception(
                "The business (ID: {$business->getKey()}) has more than 1 payment provider relating to Stripe ({$stripePaymentProviders->pluck('payment_provider')->join(', ')})."
            );
        }

        $data = $this->validate($request, [
            'registration_code' => 'required|string',
            'label' => 'required|string',
        ]);

        /** @var \App\Business\PaymentProvider $businessStripePaymentProvider */
        $businessStripePaymentProvider = $stripePaymentProviders->first();

        $businessStripeTerminalLocation = $businessManager
            ->getBusinessStripeTerminalLocations($business, $businessStripePaymentProvider);

        try {
            $stripeReader = Reader::create([
                'registration_code' => $data['registration_code'],
                'label' => $data['label'],
                'location' => $businessStripeTerminalLocation->stripe_terminal_location_id,
            ], [ 'stripe_version' => AppServiceProvider::STRIPE_VERSION ]);
        } catch (InvalidRequestException $exception) {
            $response = $exception->getJsonBody();

            if (isset($response['error']['type']) && $response['error']['type'] === 'invalid_request_error') {
                throw ValidationException::withMessages([
                    'registration_code' => "The registration code maybe invalid. (Error from Stripe: {$exception->getMessage()})",
                ]);
            }

            throw $exception;
        }

        try {
            $businessTerminal = DB::transaction(
                function (
                    Connection $connection
                ) use ($businessStripeTerminalLocation, $stripeReader, $businessStripePaymentProvider) {
                    return $businessStripeTerminalLocation->terminals()->create([
                        'name' => $stripeReader->label,
                        'payment_provider' => $businessStripePaymentProvider->payment_provider,
                        'stripe_terminal_id' => $stripeReader->id,
                        'device_type' => $stripeReader->device_type,
                        'remark' => $stripeReader->device_sw_version,
                        'data' => $stripeReader->toArray(),
                    ]);
                },
                3
            );
        } catch (Exception $exception) {
            $stripeReader->delete();

            throw $exception;
        }

        Session::flash(
            'success_message',
            "A new terminal '{$businessTerminal->name}' has been added for {$business->getName()}."
        );

        return Response::redirectToRoute('admin.business.terminal.index', $business->getKey());
    }

    public function show(Business $business, StripeTerminal $terminal)
    {
        return Response::view('admin.business.terminal-show', compact('business', 'terminal'));
    }

    /**
     * Delete the Stripe terminal from a business.
     *
     * @param  \App\Business  $business
     * @param  \App\StripeTerminal  $terminal
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Stripe\Exception\InvalidRequestException
     */
    public function destroy(Business $business, StripeTerminal $terminal) : Http\RedirectResponse
    {
        $businessPaymentProvider = $business
            ->paymentProviders()
            ->where('payment_provider', $business->payment_provider)
            ->first();

        $terminalName = $terminal->name;

        if (!$businessPaymentProvider instanceof BusinessPaymentProvider) {
            Session::flash(
                'error_message',
                "The payment provider for the business (ID : {$business->getKey()}) is not found."
            );

            goto __exit;
        }

        if ($businessPaymentProvider->payment_provider !== $business->payment_provider) {
            Session::flash(
                'error_message',
                "The payment provider for the business (ID : {$business->getKey()}) and the Stripe terminal (ID : {$terminal->getKey()}) are different. Please check."
            );

            goto __exit;
        }

        $country = $businessPaymentProvider->getConfiguration()->getCountry();

        Stripe::setApiKey(Config::get("services.stripe.{$country}.secret"));

        try {
            Reader::retrieve($terminal->stripe_terminal_id, [
                'stripe_version' => AppServiceProvider::STRIPE_VERSION,
            ])->delete();
        } catch (InvalidRequestException $exception) {
            if ($exception->getStripeCode() !== 'resource_missing') {
                throw $exception;
            }
        }

        $terminal->delete();

        Session::flash(
            'success_message',
            "The terminal '{$terminalName}' has been deleted from {$business->getName()}."
        );

        __exit:

        return Response::redirectToRoute('admin.business.terminal.index', $business->getKey());
    }
}
