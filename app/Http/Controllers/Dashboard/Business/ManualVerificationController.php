<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Actions\Business\Settings\Verification\Store;
use App\Actions\Exceptions\BadRequest;
use App\Business;
use App\Enumerations\Business\Type;
use App\Helpers\Country;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades;
use Symfony\Component\HttpFoundation\Response;

class ManualVerificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @param Request $request
     * @param Business $business
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     * @throws AuthorizationException
     * @throws \ReflectionException
     */
    public function create(Request $request, Business $business)
    {
        Facades\Gate::inspect('update', $business)->authorize();

        if ($business->verification || $business->verified_wit_my_info_sg) {
            return Facades\Response::redirectToRoute('dashboard.business.verification.home', $business->getKey());
        }

        if ($request->type) {
            $type = $request->type;
        } else {
            $type = in_array($business->business_type, [Type::COMPANY, Type::PARTNER]) ? 'company' : 'individual';
        }

        $countries = Country::getCountriesSelected($business->country);

        return Facades\Response::view('dashboard.business.verification.manual', compact(
            'business', 'type','countries'
        ));
    }

    /**
     * @param Request $request
     * @param Business $business
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|string
     * @throws AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request, Business $business)
    {
        Facades\Gate::inspect('update', $business)->authorize();

        try {
            Store::withBusiness($business)
                ->data($request->all())
                ->withRequestFile($request)
                ->setPaymentProvider()
                ->process();

            return route('dashboard.business.verification.home', $business->getKey());
        } catch (BadRequest $exception) {
            if ($request->wantsJson()) {
                return Facades\Response::json([
                    'message' => $exception->getMessage(),
                ], Response::HTTP_BAD_REQUEST);
            }

            return Facades\Response::redirectToRoute('dashboard.business.verification.home', [
                'business_id' => $business->getKey(),
            ])->with('error_message', $exception->getMessage());
        }
    }
}
