<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Actions\Business\Settings\Verification\Destroy;
use App\Actions\Business\Settings\Verification\Store;
use App\Actions\Exceptions\BadRequest;
use App\Business;
use App\Enumerations\CountryCode;
use App\Enumerations\VerificationStatus;
use App\Http\Controllers\Controller;
use GuzzleHttp\Exception\ClientException;
use HitPay\MyInfoSG;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Validator;

class VerificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function showHomepage(Business $business)
    {
        Facades\Gate::inspect('view', $business)->authorize();

        $verification = $business->verifications()->latest()->first();

        if ($verification == null) {
            if ($business->country == CountryCode::SINGAPORE) {
                return Facades\Response::view('dashboard.business.verification.verify', compact('business'));
            } else {
                // we need call flow again
                return Facades\Response::redirectToRoute('dashboard.business.verification.cognito.index', $business->getKey());
            }
        }

        if ($verification &&
            !$verification->isVerified() &&
            $business->country != CountryCode::SINGAPORE) {
            return Facades\Response::redirectToRoute('dashboard.business.verification.cognito.show', [
                $business->getKey(),
                $verification->getKey(),
            ]);
        }

        if (!$verification->isVerified() && $verification->status == VerificationStatus::VERIFIED) {
            // user have finish go to my info but not yet confirm
            return Facades\Response::redirectToRoute('dashboard.business.verification.confirm.page', [
                $business->getKey(),
                $verification->getKey(),
            ]);
        }

        // get from submitted data
        $verification_data = $verification->verificationData('submitted');

        $type = $verification->type === 'business' ? 'company' : 'individual';

        $businessPersons = $verification->persons()->get();

        if ($type == 'company') {
            $verification_data = $this->setShareholderData($verification, $verification_data, $businessPersons);
        }

        return Facades\Response::view('dashboard.business.verification.completed',
            compact(
                'business',
                'verification',
                'verification_data',
                'type'
            )
        );
    }

    private function setShareholderData($verification, $verification_data, $businessPersons)
    {
        if (isset($verification_data['shareholders'])) {
            $shareholders = $verification_data['shareholders'];
        } else {
            $shareholders = [];
        }

        $verification_data['shareholders_count'] = count($shareholders);
        $verification_data['shareholders_first_name'] = [];
        $verification_data['shareholders_first_name_error'] = [];
        $verification_data['shareholders_last_name'] = [];
        $verification_data['shareholders_last_name_error'] = [];
        $verification_data['shareholders_error'] = [];
        $verification_data['shareholders_id_number'] = [];
        $verification_data['shareholders_id_number_error'] = [];
        $verification_data['shareholders_is_director'] = [];
        $verification_data['shareholders_is_owner'] = [];
        $verification_data['shareholders_is_executive'] = [];
        $verification_data['shareholders_dob'] = [];
        $verification_data['shareholders_dob_error'] = [];
        $verification_data['shareholders_address'] = [];
        $verification_data['shareholders_address_error'] = [];
        $verification_data['shareholders_postal'] = [];
        $verification_data['shareholders_postal_error'] = [];
        $verification_data['shareholders_title'] = [];
        $verification_data['shareholders_title_error'] = [];
        $verification_data['shareholders_email'] = [];
        $verification_data['shareholders_email_error'] = [];
        $verification_data['shareholders_relationship_error'] = [];

        for ($i=0; $i<$verification_data['shareholders_count']; $i++) {
            $verification_data['shareholders_first_name'][] = "";
            $verification_data['shareholders_first_name_error'][] = "";
            $verification_data['shareholders_last_name'][] = "";
            $verification_data['shareholders_last_name_error'][] = "";
            $verification_data['shareholders_error'][] = "";
            $verification_data['shareholders_id_number'][] = "";
            $verification_data['shareholders_id_number_error'][] = "";
            $verification_data['shareholders_is_director'][] = 'no';
            $verification_data['shareholders_is_owner'][] = 'no';
            $verification_data['shareholders_is_executive'][] = 'no';
            $verification_data['shareholders_dob'][] = "";
            $verification_data['shareholders_dob_error'][] = "";
            $verification_data['shareholders_address'][] = "";
            $verification_data['shareholders_address_error'][] = "";
            $verification_data['shareholders_postal'][] = "";
            $verification_data['shareholders_postal_error'][] = "";
            $verification_data['shareholders_title'][] = "";
            $verification_data['shareholders_title_error'][] = "";
            $verification_data['shareholders_email'][] = "";
            $verification_data['shareholders_email_error'][] = "";
            $verification_data['shareholders_relationship_error'][] = "";
        }

        if (!isset($businessPersons)) {
            $businessPersons = $verification->persons()->get();
        }

        if ($businessPersons->count() > 0) {
            $totalPersonWithoutRepresentative = $businessPersons->count() - 1;

            if ($totalPersonWithoutRepresentative == count($shareholders)) {
                // generate data for stripe
                foreach ($businessPersons as $keyIndex => $businessPerson) {
                    $relationships = $businessPerson->relationship;

                    if (
                        is_array($relationships) &&
                        array_key_exists('representative', $relationships) &&
                        $relationships['representative'] == true) {
                        // skip adding user representative to view
                        continue;
                    }

                    $verification_data['shareholders_first_name'][$keyIndex] = $businessPerson->first_name;
                    $verification_data['shareholders_last_name'][$keyIndex] = $businessPerson->last_name;
                    $verification_data['shareholders_id_number'][$keyIndex] = $businessPerson->id_number;
                    $verification_data['shareholders_dob'][$keyIndex] = $businessPerson->dob;
                    $verification_data['shareholders_address'][$keyIndex] = $businessPerson->address;
                    $verification_data['shareholders_postal'][$keyIndex] = $businessPerson->postal_code;
                    $verification_data['shareholders_title'][$keyIndex] = $businessPerson->title;
                    $verification_data['shareholders_email'][$keyIndex] = $businessPerson->email;

                    $verification_data['shareholders_is_owner'][$keyIndex] = 'no';
                    $verification_data['shareholders_is_director'][$keyIndex] = 'no';
                    $verification_data['shareholders_is_executive'][$keyIndex] = 'no';

                    if (is_array($relationships)) {
                        foreach ($relationships as $key => $relationship) {
                            if ($key == 'owner' && $relationship === true) {
                                $verification_data['shareholders_is_owner'][$keyIndex] = 'yes';
                            }

                            if ($key == 'director' && $relationship === true) {
                                $verification_data['shareholders_is_director'][$keyIndex] = 'yes';
                            }

                            if ($key == 'executive' && $relationship === true) {
                                $verification_data['shareholders_is_executive'][$keyIndex] = 'yes';
                            }
                        }
                    }
                }
            }
        }

        return $verification_data;
    }

    public function redirect(Request $request, Business $business, string $type)
    {
        Facades\Gate::inspect('update', $business)->authorize();

        if ($type !== 'business' && $type !== 'personal') {
            throw new NotFoundHttpException;
        }

        $cacheKey = (int)microtime(true) * 1000;

        $request->session()->put('my_info_sg', [
            'key' => $cacheKey,
            'data' => [
                'verification_type' => $type,
                'business_id' => $business->getKey(),
            ],
        ]);

        return Facades\Redirect::to((new MyInfoSG\Service($type))->getAuthoriseUrl($cacheKey));
    }

    public function callback(Request $request)
    {
        if ($request->get('error')) {
            throw new HttpException(403, $request->get('error-description', 'Authorization failed.'));
        }

        switch (true) {
            case is_null($data = $request->session()->get('my_info_sg')):
            case $data['data']['verification_type'] !== 'business' && $data['data']['verification_type'] !== 'personal':
            case (int)$request->get('state') !== $data['key']:
            case !(isset($data['data']['verification_type']) && isset($data['data']['business_id'])):
            case !($business = Business::find($data['data']['business_id'])) instanceof Business:
            case !($code = $request->get('code')):
                throw new HttpException(419);
        }

        Facades\Gate::inspect('update', $business)->authorize();

        $service = new MyInfoSG\Service($data['data']['verification_type']);

        try {
            $accessToken = $service->getAccessToken($code);
        } catch (ClientException $exception) {
            throw new HttpException(419);
        }

        try {
            $information = $service->getInformation($accessToken);
        } catch (ClientException $exception) {
            Facades\Log::info("Failed to get information from MyInfoSg for business #{$business->getKey()}. Error: {$exception->getMessage()} ({$exception->getFile()}:{$exception->getLine()})");

            throw new HttpException(419);
        }

        $verification = $business->verifications()->create([
            'type' => $data['data']['verification_type'],
            'my_info_data' => $information,
            'status' => VerificationStatus::VERIFIED
        ]);

        return Facades\Response::redirectToRoute('dashboard.business.verification.confirm.page', [
            $business->getKey(),
            $verification->getKey(),
        ]);
    }

    public function callbackSandbox(Request $request)
    {
        if (Facades\App::environment('local')) {
            return $this->callback($request);
        }

        throw new NotFoundHttpException;
    }

    public function showManualPage(Request $request, Business $business)
    {
        Facades\Gate::inspect('update', $business)->authorize();

        if ($business->verification || $business->verified_wit_my_info_sg) {
            return Facades\Response::redirectToRoute('dashboard.business.verification.home', $business->getKey());
        }

        if ($request->type) {
            $type = $request->type;
        } else {
            $type = $business->business_type === 'company' ? 'company' : 'individual';
        }

        return Facades\Response::view('dashboard.business.verification.manual', compact('business', 'type'));
    }

    public function showConfirmPage(Business $business, Business\Verification $verification)
    {
        Facades\Gate::inspect('update', $business)->authorize();

        if ($business->getKey() !== $verification->business_id) {
            throw new AuthorizationException;
        } elseif ($verification->verified_at || $business->verified_wit_my_info_sg) {
            return Facades\Response::redirectToRoute('dashboard.business.verification.home', $business->getKey());
        }

        $isMoreConfirm = false;
        $businessPersons = null;

        if (!$verification->isVerified() && $verification->status == VerificationStatus::VERIFIED) {
            $businessPersons = $verification->persons()->get();

            if ($businessPersons->count() == 0) {
                $isMoreConfirm = true;
            }
        }

        $verification_data = $verification->verificationData();

        $type = $verification->type === 'business' ? 'company' : 'individual';

        if ($type == 'company') {
            $verification_data = $this->setShareholderData($verification, $verification_data, $businessPersons);
        }

        return Facades\Response::view('dashboard.business.verification.confirm', compact(
            'business', 'verification',
            'verification_data', 'type',
            'isMoreConfirm'
        ));
    }

    public function confirm(Request $request, Business $business, Business\Verification $verification = null)
    {
        Facades\Gate::inspect('update', $business)->authorize();

        if ($verification == null && !$request->has('verification')) {
            return Facades\Response::redirectToRoute('dashboard.business.verification.home', $business->getKey());
        }

        if (!$verification == null && $business->getKey() !== $verification->business_id) {
            throw new AuthorizationException;
        } elseif (!$verification == null && $verification->verified_at) {
            return Facades\Response::redirectToRoute('dashboard.business.verification.home', $business->getKey());
        }

        if ($verification == null) {
            try {
                Store::withBusiness($business)
                    ->data($request->all())
                    ->withRequestFile($request)
                    ->setPaymentProvider()
                    ->process();
            } catch (BadRequest $exception) {
                if ($request->wantsJson()) {
                    return Facades\Response::json([
                        'message' => $exception->getMessage(),
                    ], \Illuminate\Http\Response::HTTP_BAD_REQUEST);
                }

                return Facades\Response::redirectToRoute('dashboard.business.verification.home', [
                    'business_id' => $business->getKey(),
                ])->with('error_message', $exception->getMessage());
            }
        }

        if ($request->fill_type == 'manual') {
            Session::flash('success_message', 'Your account verification has been submitted, you will be notified once the account is verified. You can start accepting payments.');
        } else {
            Session::flash('success_message', 'Your account verification has been completed. You can start accepting payments.');
        }

        return route('dashboard.business.verification.home', $business->getKey());
    }

    public function delete(Request $request, Business $business, Business\Verification $verification)
    {
        Facades\Gate::inspect('update', $business)->authorize();

        if ($business->getKey() !== $verification->business_id) {
            throw new AuthorizationException;
        }

        Destroy::withBusiness($business)
            ->process();

        return route('dashboard.business.verification.home', $business->getKey());
    }

    protected function getCacheKey(Request $request): string
    {
        return '_for_verification:' . $request->session()->getId();
    }
}
