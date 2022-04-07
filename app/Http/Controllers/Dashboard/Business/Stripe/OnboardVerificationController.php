<?php

namespace App\Http\Controllers\Dashboard\Business\Stripe;

use App\Actions\Business\Stripe\VerificationOnboard\UpdateBusinessCompany;
use App\Actions\Business\Stripe\VerificationOnboard\UpdateBusinessPerson;
use App\Actions\Business\Stripe\VerificationOnboard\UpdateIndividualPerson;
use App\Actions\Business\Stripe\VerificationOnboard\UpdateIndividualCompany;
use App\Actions\Business\Stripe\VerificationOnboard\AcceptAgreement;
use App\Actions\Exceptions\BadRequest;
use App\Business;
use App\Enumerations\AllCountryCode;
use App\Exceptions\HitPayLogicException;
use App\Http\Controllers\Controller;
use HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException;
use HitPay\Stripe\CustomAccount\Exceptions\GeneralException;
use HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades;
use Illuminate\Http\Request;
use Illuminate\Http;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;
use Stripe\Exception\ApiErrorException;
use Throwable;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class OnboardVerificationController extends Controller
{
    /**
     * OnboardVerificationController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @param Business $business
     * @return Http\RedirectResponse|Http\Response
     * @throws AuthorizationException
     * @throws ReflectionException
     */
    public function show(Business $business)
    {
        Facades\Gate::inspect('view', $business)->authorize();

        $verification = $business->verifications()->latest()->first();

        if ($verification === null) {
            return Facades\Response::redirectToRoute('dashboard.business.verification.home', $business->getKey());
        }

        $provider = $business->paymentProviders()->where('payment_provider', $business->payment_provider)->first();

        if ($provider && $provider->payment_provider_account_type !== 'custom') {
            return Response::redirectToRoute(
                'dashboard.business.payment-provider',
                [ 'business_id' => $business->getKey() ]
            );
        }

        $type = $business->business_type;

        $documentCompany = null;
        if ($type == 'company') {
            $documentCompany = $provider->files()
                ->where('group', 'stripe_file_tax')
                ->latest()
                ->first();
        }

        $account = $provider->data['account'];
        $businessPersons = $provider->persons()->get();

        $persons = [];
        foreach ($businessPersons as $businessPerson) {
            $personData = $businessPerson->data;
            $personData['file'] = $businessPerson->files()->latest()->first();
            $persons[] = $personData;
        }

        $countriesList = [];
        foreach (AllCountryCode::listConstants() as $code) {
            if (Lang::has('misc.country.'.$code)) {
                $countryName = Lang::get('misc.country.'.$code);
            } else {
                $countryName = strtoupper($code);
            }

            $countriesList[$code] = [
                'code' => strtoupper($code),
                'name' => $countryName,
            ];
        }

        $countriesList = Collection::make($countriesList);
        $countries = $countriesList->sortBy('name');

        return Response::view('dashboard.business.payment-providers.stripe.onboard-verification',
            compact(
                'business', 'provider',
                'type', 'account', 'persons',
                'countries',
                'documentCompany'
            )
        );
    }

    /**
     * @param Request $request
     * @param Business $business
     * @return Http\JsonResponse
     * @throws HitPayLogicException
     * @throws AccountNotFoundException
     * @throws GeneralException
     * @throws InvalidStateException
     * @throws AuthorizationException
     * @throws InvalidArgumentException
     * @throws ApiErrorException
     * @throws Throwable
     */
    public function store(Request $request, Business $business): Http\JsonResponse
    {
        Facades\Gate::inspect('update', $business)->authorize();

        // check update company or individual
        $type = $request->input('type', null);

        // check person update or company update or tos action
        $updateTypeFor = $request->input('update_type_for', null);

        if (!in_array($type, ['individual', 'business'])) {
            throw new \Exception("Type not found");
        }

        if (!in_array($updateTypeFor,['person', 'company', 'tos'])) {
            throw new \Exception("Update Type For not found");
        }

        if ($type == 'individual') {
            if ($updateTypeFor == 'person') {
                try {
                    $withDocument = $request->input('with_document');

                    if ($withDocument === 'yes') {
                        $businessPaymentProvider = UpdateIndividualPerson::withData($request->all())
                            ->withRequestFile($request)
                            ->business($business)
                            ->process();
                    } else {
                        // if verified account, cant upload new document/update document.
                        $businessPaymentProvider = UpdateIndividualPerson::withData($request->all())
                            ->business($business)
                            ->process();
                    }

                    return Facades\Response::json([
                        'businessPaymentProvider' => $businessPaymentProvider,
                    ], ResponseAlias::HTTP_OK);
                } catch (BadRequest $exception) {
                    return Facades\Response::json([
                        'message' => $exception->getMessage(),
                    ], ResponseAlias::HTTP_BAD_REQUEST);
                }
            }

            if ($updateTypeFor == 'company') {
                try {
                    $businessPaymentProvider = UpdateIndividualCompany::withData($request->all())
                        ->business($business)
                        ->process();

                    return Facades\Response::json([
                        'businessPaymentProvider' => $businessPaymentProvider,
                    ], ResponseAlias::HTTP_OK);
                } catch (BadRequest $exception) {
                    return Facades\Response::json([
                        'message' => $exception->getMessage(),
                    ], ResponseAlias::HTTP_BAD_REQUEST);
                }
            }
        } else {
            if ($updateTypeFor == 'person') {
                try {
                    $withDocument = $request->input('with_document');

                    if ($withDocument == 'yes') {
                        $businessPaymentProvider = UpdateBusinessPerson::withData($request->all())
                            ->withRequestFile($request)
                            ->business($business)
                            ->process();
                    } else {
                        // if verified account, cant upload new document/update document.
                        $businessPaymentProvider = UpdateBusinessPerson::withData($request->all())
                            ->business($business)
                            ->process();
                    }

                    return Facades\Response::json([
                        'businessPaymentProvider' => $businessPaymentProvider,
                    ], ResponseAlias::HTTP_OK);
                } catch (BadRequest $exception) {
                    return Facades\Response::json([
                        'message' => $exception->getMessage(),
                    ], ResponseAlias::HTTP_BAD_REQUEST);
                }
            }

            if ($updateTypeFor == 'company') {
                try {
                    $withDocument = $request->input('with_document');

                    if ($withDocument == 'yes') {
                        $businessPaymentProvider = UpdateBusinessCompany::withData($request->all())
                            ->withRequestFile($request, 'stripe_file_tax')
                            ->business($business)
                            ->process();
                    } else {
                        $businessPaymentProvider = UpdateBusinessCompany::withData($request->all())
                            ->business($business)
                            ->process();
                    }

                    return Facades\Response::json([
                        'businessPaymentProvider' => $businessPaymentProvider,
                    ], ResponseAlias::HTTP_OK);
                } catch (BadRequest $exception) {
                    return Facades\Response::json([
                        'message' => $exception->getMessage(),
                    ], ResponseAlias::HTTP_BAD_REQUEST);
                }
            }
        }

        if ($updateTypeFor == 'tos') {
            try {
                $businessPaymentProvider = AcceptAgreement::withData($request->all())
                    ->business($business)
                    ->process($request);

                return Facades\Response::json([
                    'businessPaymentProvider' => $businessPaymentProvider,
                ], ResponseAlias::HTTP_OK);
            } catch (BadRequest $exception) {
                return Facades\Response::json([
                    'message' => $exception->getMessage(),
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }
        }

        throw new HitPayLogicException("Unhandled process");
    }
}
