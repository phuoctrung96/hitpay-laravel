<?php

namespace App\Http\Controllers\MigratedApi;

use App\Http\Controllers\Controller;
use App\Transformers\UserDetails;
use HitPay\Support\Controllers\StandardizeJsonResponses;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    // use StandardizeJsonResponses;

    /**
     * SettingController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function enableCart(Request $request)
    {
        /** @var \App\User $user */
        $user = $request->user();

        $currencyGroups = $user->product()->where(function (Builder $query) use ($user) {
            $query->whereNull('currency_code');
            $query->orWhere('currency_code', '<>', $user->default_currency_code);
        })->get();

        if ($currencyGroups->count() >= 1) {
            return $this->setStatusCode(400)->message('You have product with different currencies, change the currency to '.$user->default_currency_code.' before enable the cart again.');
        }

        $shippingMethods = $user->shippingMethod()->get();

        if ($shippingMethods->count() < 1) {
            return $this->setStatusCode(400)->message('You don\'t have any master shipping methods set, create a shipping method before enable the cart again.');
        }

        // todo run a script to check all the product, if with shipping country, set the deliverable to enable
        $user->is_cart_enabled = true;

        $user->save();

        return $this->model($user, new UserDetails);
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function setStoreUrl(Request $request)
    {
        /** @var \App\User $user */
        $user = $request->user();

        $data = $this->validate($request, [
            'username' => [
                'required',
                'alpha_num',
                'max:32',
                Rule::unique('accounts', 'username')->ignore($user->id),
            ],
        ]);

        $user->username = $data['username'];
        $user->save();

        return $this->model($user, new UserDetails);
    }
}
