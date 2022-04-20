<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Business as BusinessModel;
use App\Business\Shipping as ShippingModel;
use App\Business\ShippingDiscount;
use App\Enumerations\AllCountryCode;
use App\Enumerations\Business\ShippingCalculation;
use App\Enumerations\CountryCode;
use App\Http\Controllers\Controller;
use App\Http\Resources\Business\Shipping;
use App\Logics\Business\ShippingRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\DB;

class ShippingController extends Controller
{
    /**
     * ShippingController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(BusinessModel $business)
    {
        Gate::inspect('view', $business)->authorize();

        $shippings = $business->shippings()->with('countries')->get();

        $countries = null;

        foreach ($shippings as $key => $shipping) {
            if ($shipping->countries->count()) {
                $countries[] = $shipping->countries->pluck('country')->map(function ($value) {
                    return [
                        'code' => $value,
                        'name' => trans('misc.country.' . $value),
                    ];
                })->sortBy('name');
            }
            else {
                $countries[] = [
                        'code' => 'global',
                        'name' => Lang::get('misc.global'),
                    ];
            }
        }
        $shipping_discount = $business->shipping_discount()->first();

        return Response::view('dashboard.business.shipping.index', compact('business', 'shippings', 'countries', 'shipping_discount'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \ReflectionException
     */
    public function create(Request $request, BusinessModel $business)
    {
        Gate::inspect('update', $business)->authorize();

        $data = $this->formData();

        return Response::view('dashboard.business.shipping.form', compact('business', 'data'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function store(Request $request, BusinessModel $business)
    {
        Gate::inspect('update', $business)->authorize();

        $request->merge([
            'active' => true,
        ]);

        $shipping = ShippingRepository::store($request, $business);

        Session::flash('success_message', 'The shipping ' . $shipping->name . ' has been added successfully.');

        return Response::json([
            'redirect_url' => URL::route('dashboard.business.setting.shipping.home', $business->getKey()),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Business $business
     * @param \App\Business\Shipping $shipping
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \ReflectionException
     */
    public function edit(BusinessModel $business, ShippingModel $shipping)
    {
        Gate::inspect('view', $business)->authorize();

        $data = $this->formData();

        $country = $shipping->countries()->first();

        $shipping = $shipping->only([
            'calculation',
            'country',
            'currency',
            'description',
            'id',
            'name',
            'rate',
            'slots'
        ]);

        $shipping['rate'] = getReadableAmountByCurrency($business->currency, $shipping['rate']);
        $shipping['slots'] = json_decode($shipping['slots']);

        if ($country) {
            $shipping['country'] = $country->country;
        }

        return Response::view('dashboard.business.shipping.form', compact('business', 'shipping', 'data'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\Shipping $shipping
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function update(Request $request, BusinessModel $business, ShippingModel $shipping)
    {
        Gate::inspect('update', $business)->authorize();

        $request->merge([
            'active' => true,
        ]);

        ShippingRepository::update($request, $business, $shipping);

        Session::flash('success_message', 'The shipping \'' . $shipping->name . '\' has been updated successfully.');

        return Response::json([
            'redirect_url' => URL::route('dashboard.business.setting.shipping.home', $business->getKey()),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Business $business
     * @param \App\Business\Shipping $shipping
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function destroy(BusinessModel $business, ShippingModel $shipping)
    {
        Gate::inspect('update', $business)->authorize();

        if ($business->shippings()->count() === 1) {
            return Response::json([
                'message' => 'You can\'t delete the only one shipping method in the business.',
            ], 403);
        }

        $shippingName = $shipping->name;

        ShippingRepository::delete($shipping);

        Session::flash('success_message', 'The shipping \'' . $shippingName . '\' has been deleted successfully.');

        return Response::json([
            'redirect_url' => URL::route('dashboard.business.setting.shipping.home', $business->getKey()),
        ]);
    }

    /**
     * @param Request $request
     * @param Business $business
     * @return \Illuminate\Http\JsonResponse
     * @throws ValidationException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function storeDiscount(Request $request, BusinessModel $business){
        Gate::inspect('manage', $business)->authorize();

        $discountID = $request->get('id');
        $requestData = $this->validate($request, [
            'fixed_amount' => 'required|decimal:0,2',
            'percentage' => 'required|decimal:4,5',
            'minimum_cart_amount' => 'required|decimal:0,2',
            'type' => 'required|string'
            ]);
        try {
            $requestData['minimum_cart_amount'] = getRealAmountForCurrency($business->currency, $requestData['minimum_cart_amount']);
            $requestData['fixed_amount'] = getRealAmountForCurrency($business->currency, $requestData['fixed_amount']);

            DB::beginTransaction();
            if(isset($discountID))
            {
                $business->shipping_discount()->where('id', $discountID)->update($requestData);
            }
            else {
                $business->shipping_discount()->create($requestData);
            }
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
        Session::flash('success_message', !isset($discountID)? 'The discount has been created.'
        :'Successfully updated');
        return Response::json([
            'redirect_url' => URL::route('dashboard.business.setting.shipping.home', [
                $business->getKey(),
            ]),
        ]);
    }

    /**
     * Delete the specified shipping discount.
     *
     * @param \App\Business $business
     * @param \App\Business\ShippingDiscount $shipping_discount
     */
    public function deleteDiscount(BusinessModel $business, ShippingDiscount $shipping_discount){
        if (!isset($shipping_discount->id))
        {
            App::abort(404);
        }
        try {
            $shipping_discount->delete();
        }
        catch (\Exception $exception)
        {
            throw $exception;
        }
        Session::flash('success_message', 'Successfully deleted');
        return redirect()->back();
    }

    /**
     * @return mixed
     * @throws \ReflectionException
     */
    private function formData()
    {
        $countries = new Collection;

        foreach (AllCountryCode::listConstants() as $value) {
            if (Lang::has('misc.country.' . $value)) {
                $name = Lang::get('misc.country.' . $value);
            } else {
                $name = $value;
            }

            $countries->add([
                'code' => $value,
                'name' => $name,
            ]);
        }

        $data['countries'][] = [
            'code' => 'global',
            'name' => 'Global',
        ];
        $data['countries'] = array_merge($data['countries'], $countries->sortBy('name')->values()->toArray());

        $data['calculations'] = [
            [
                'code' => ShippingCalculation::FLAT,
                'name' => Lang::get('misc.shipping_calculation.' . ShippingCalculation::FLAT),
            ],
            [
                'code' => ShippingCalculation::FEE_PER_UNIT,
                'name' => Lang::get('misc.shipping_calculation.' . ShippingCalculation::FEE_PER_UNIT),
            ],
        ];

        return $data;
    }
}
