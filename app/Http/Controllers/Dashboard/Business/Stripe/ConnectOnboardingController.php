<?php

namespace App\Http\Controllers\Dashboard\Business\Stripe;

use App\Actions\Business\Stripe\ConnectOnboard;
use App\Actions\Exceptions\BadRequest;
use App\Business;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades;
use Illuminate\Http\Request;
use Illuminate\Http;

class ConnectOnboardingController extends Controller
{
    /**
     * ConnectOnboardingController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @param Request $request
     * @param Business $business
     * @return Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Throwable
     */
    public function store(Request $request, Business $business)
    {
        Facades\Gate::inspect('update', $business)->authorize();

        try {
            $url = ConnectOnboard::withData($request->all())
                ->business($business)
                ->process();

            return Facades\Response::json([
                'url' => $url->getTargetUrl(),
            ], Http\Response::HTTP_OK);
        } catch (BadRequest $exception) {
            return Facades\Response::json([
                'message' => $exception->getMessage(),
            ], Http\Response::HTTP_BAD_REQUEST);
        }
    }
}
