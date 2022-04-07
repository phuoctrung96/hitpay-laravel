<?php

namespace App\Http\Controllers\Dashboard\Business\Onboard;

use App\Actions\Business\Onboard\Paynow\Store;
use App\Actions\Exceptions\BadRequest;
use App\Business;
use App\Http\Controllers\Controller;
use HitPay\Data\Countries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades;
use Symfony\Component\HttpFoundation\Response;

class PaynowController extends Controller
{
    /**
     * PaynowController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /***
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Business  $business
     *
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function create(Request $request, Business $business)
    {
        Facades\Gate::inspect('view', $business)->authorize();

        if ($business->bankAccounts()->count() > 0) {
            return Facades\Response::redirectToRoute('dashboard.home', [
                'business_id' => $business->getKey(),
            ]);
        }

        $data = $this->validate($request, [
            'success_message' => 'string|max:64|nullable',
        ]);

        return Facades\Response::view('dashboard.business.onboard.paynow.create', [
            'business' => $business,
            'provider' => null,
            'banks_list' => $business->banksAvailable()->toArray(),
            'success_message' => empty($data['success_message']) ? '' : $data['success_message'],
        ]);
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Business  $business
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function store(Request $request, Business $business)
    {
        Facades\Gate::inspect('update', $business)->authorize();

        try {
            Store::withBusiness($business)->data($request->all())->process();
        } catch (BadRequest $exception) {
            if ($request->wantsJson()) {
                return Facades\Response::json([
                    'message' => $exception->getMessage(),
                ], Response::HTTP_BAD_REQUEST);
            }

            return Facades\Response::redirectToRoute('dashboard.business.onboard.paynow', [
                'business_id' => $business->getKey(),
            ])->with('error_message', $exception->getMessage());
        }

        $nextUrl = route("dashboard.home");

        return Facades\Response::json([
            'success_message' => 'You have setup bank account successfully.',
            'url' => $nextUrl,
        ]);
    }
}
