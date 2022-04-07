<?php

namespace App\Http\Controllers\MigratedApi;

use App\Business;
use App\Enumerations\Business\Event;
use App\Enumerations\Business\NotificationChannel;
use App\Enumerations\Business\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * UserController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api')->except('getLogo');
    }

    /**
     * Get user object.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \ReflectionException
     */
    public function show(Request $request)
    {
        $business = $this->getBusiness($request);

        Gate::inspect('view', $business)->authorize();

        return $this->generateUserObjectResponse($business, true);
    }

    /**
     * Update store URL.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function setStoreUrl(Request $request)
    {
        $business = $this->getBusiness($request);

        Gate::inspect('update', $business)->authorize();

        $data = $this->validate($request, [
            'username' => [
                'required',
                'alpha_num',
                'max:32',
                Rule::unique('businesses', 'identifier')->ignore($business->getKey()),
            ],
        ]);

        $business->identifier = $data['username'];

        $business = DB::transaction(function () use ($business) {
            $business->save();

            return $business;
        });

        return $this->generateUserObjectResponse($business, true);
    }

    /**
     * Generate user object response.
     *
     * @param \App\Business $business
     * @param bool $loadUserObject
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \ReflectionException
     */
    private function generateUserObjectResponse(Business $business, bool $loadUserObject = false)
    {
        $data['id'] = $business->id;
        $data['username'] = $business->identifier;
        $data['store_url'] = $business->identifier
            ? route('shop.business', $business->identifier)
            : route('shop.business', $business->id);

        /**
         * @var \App\Business\PaymentProvider $paymentProvider
         */
        $paymentProvider = $business->paymentProviders->where('payment_provider', 'stripe_sg')->first();

        $data['auth_provider'] = $paymentProvider->payment_provider === 'stripe_sg' ? 'stripe' : null;
        // $data['auth_id'] = $paymentProvider->payment_provider_account_id;
        $data['auth_id'] = 'acct_19aER1AMHowMCIhZ'; // a hack to override the account ID.

        $data['extra_data'] = Arr::only($paymentProvider->data, [
            'email',
            'statement_descriptor',
            'support_email',
            'support_phone',
            'support_url',
        ]);

        $data['email'] = $business->email;
        $data['phone_number'] = $business->phone_number;

        if ($loadUserObject || $business->relationLoaded('logo')) {
            $data['logo_url'] = $business->logo ? $business->logo->getUrl() : null;
        }

        $data['payment_methods'][] = 'payment_card';
        $data['payment_methods'][] = 'wechat';
        $data['payment_methods'][] = 'paynow';

        $data['name'] = $business->name;
        $data['display_name'] = $business->display_name;
        $data['business_category'] = $business->category;
        $data['referral'] = [
            'code' => 'Not available',
            'url' => 'Not available',
        ];
        $data['country_code'] = strtoupper($business->country);
        $data['default_currency_code'] = strtoupper($business->currency);
        $data['is_activated'] = $business->is_email_verified;
        $data['is_verified'] = $business->is_verified;
        $data['is_cart_enabled'] = 1;
        $data['created_at'] = $business->created_at->getTimestamp();

        if ($business->relationLoaded('subscribedEvent')) {
            foreach (Event::listConstants() as $event) {
                foreach (NotificationChannel::listConstants() as $channel) {
                    $exists = $business->subscribedEvents->where('event', $event)->where('channel', $channel)->first();

                    if ($channel === 'push_notification') {
                        $channel = 'mobile_notification';
                    }

                    switch ($event) {

                        case 'new_checkout_order':
                            $event = 'new_checkout_order';

                            break;

                        case 'low_quantity_alert':
                            $event = 'low_quantity';

                            break;
                    }

                    $data['subscriptions'][$channel.'@'.$event] = $exists ? true : false;
                }
            }
        }

        if ($loadUserObject) {
            $data['pending_order_count'] = $business->orders()->whereIn('status', [
                OrderStatus::REQUIRES_BUSINESS_ACTION,
                OrderStatus::REQUIRES_CUSTOMER_ACTION,
                OrderStatus::REQUIRES_PAYMENT_METHOD,
            ])->count();
        }

        return Response::json($data);
    }
}
