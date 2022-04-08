<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Actions\Business\Settings\Verification\Cognito\Index;
use App\Actions\Business\Settings\Verification\Cognito\Show;
use App\Actions\Business\Settings\Verification\Cognito\Store;
use App\Actions\Exceptions\BadRequest;
use App\Business;
use App\Enumerations\CountryCode;
use App\Exceptions\HitPayLogicException;
use App\Http\Controllers\Controller;
use Exception;
use HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException;
use HitPay\Stripe\CustomAccount\Exceptions\GeneralException;
use HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades;
use Illuminate\Support\Facades\Session;
use Psr\SimpleCache\InvalidArgumentException;
use Stripe\Exception\ApiErrorException;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Throwable;
use Validator;

class VerificationCognitoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Business $business)
    {
        Facades\Gate::inspect('view', $business)->authorize();

        $verification = $business->verifications()->latest()->first();

        if ($verification !== null) {
            if ($business->country == CountryCode::SINGAPORE) {
                // redirect to my info with exists verification
                return Facades\Response::redirectToRoute('dashboard.business.verification.home', $business->getKey());
            } else {
                if ($verification->verification_provider_status == 'success') {
                    // redirect to show cognito controller
                    return Facades\Response::redirectToRoute('dashboard.business.verification.cognito.show', [
                        $business->getKey(),
                        $verification->getKey()
                    ]);
                }
            }
        }

        if ($verification == null) {
            if ($business->country == CountryCode::SINGAPORE) {
                // redirect to my info
                return Facades\Response::redirectToRoute('dashboard.business.verification.home', $business->getKey());
            }
        }

        $indexData = Index::withBusiness($business)->process();

        return Facades\Response::view('dashboard.business.verification.cognito.index', $indexData);
    }

    /**
     * @param Business $business
     * @param Business\Verification $verification
     * @throws AuthorizationException
     * @throws Exception
     */
    public function show(Business $business, Business\Verification $verification)
    {
        Facades\Gate::inspect('view', $business)->authorize();

        if ($verification->verification_provider_status != 'success') {
            return Facades\Response::redirectToRoute('dashboard.business.verification.cognito.index', [
                $business->getKey()
            ]);
        }

        $showData = Show::withBusiness($business)->setVerification($verification)
            ->process();

        return Facades\Response::view('dashboard.business.verification.cognito.show', $showData);
    }

    /**
     * @param Request $request
     * @param Business $business
     * @param Business\Verification $verification
     * @return JsonResponse|RedirectResponse|string
     * @throws AuthorizationException
     * @throws HitPayLogicException
     * @throws AccountNotFoundException
     * @throws GeneralException
     * @throws InvalidStateException
     * @throws InvalidArgumentException
     * @throws ApiErrorException
     * @throws Throwable
     */
    public function store(Request $request, Business $business, Business\Verification $verification)
    {
        Facades\Gate::inspect('update', $business)->authorize();

        if (!$verification == null && $business->getKey() !== $verification->business_id) {
            throw new AuthorizationException;
        }

        try {
            Store::withBusiness($business)
                ->data($request->all())
                ->withRequestFile($request)
                ->setVerification($verification)
                ->setPaymentProvider()
                ->process();
        } catch (BadRequest $exception) {
            if ($request->wantsJson()) {
                return Facades\Response::json([
                    'message' => $exception->getMessage(),
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            return Facades\Response::redirectToRoute('dashboard.business.verification.cognito.index', [
                'business_id' => $business->getKey(),
            ])->with('error_message', $exception->getMessage());
        }

        Session::flash('success_message', 'Your account verification has been submitted, you will be notified once the account is verified.');

        return route('dashboard.business.verification.cognito.index', $business->getKey());
    }
}
