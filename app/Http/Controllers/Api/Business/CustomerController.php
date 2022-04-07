<?php

namespace App\Http\Controllers\Api\Business;

use App\Business as BusinessModel;
use App\Business\Customer as CustomerModel;
use App\Enumerations\Business\ChargeStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Business\Charge;
use App\Http\Resources\Business\Customer;
use App\Logics\Business\CustomerRepository;
use App\Manager\CustomerManagerInterface;
use App\Http\Requests\CreateCustomerByEmailRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;

class CustomerController extends Controller
{
    /**
     * CustomerController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Display a listing of the resource.
     *
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(BusinessModel $business)
    {
        Gate::inspect('view', $business)->authorize();

        return Customer::collection($business->customers()->paginate());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \App\Http\Resources\Business\Customer
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function store(Request $request, BusinessModel $business)
    {
        Gate::inspect('update', $business)->authorize();

        $customer = CustomerRepository::store($request, $business);

        return new Customer($customer);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Business $business
     * @param \App\Business\Customer $customer
     *
     * @return \App\Http\Resources\Business\Customer
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(BusinessModel $business, CustomerModel $customer)
    {
        Gate::inspect('view', $business)->authorize();

        return new Customer($customer);
    }

    /**
     * Display a listing of the resource
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\Customer $customer
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function chargesIndex(Request $request, BusinessModel $business, CustomerModel $customer)
    {
        Gate::inspect('view', $business)->authorize();

        $charges = $this->requestHelperForBusinessWith($request, $customer->charges());

        $charges->whereIn('status', [
            ChargeStatus::REFUNDED,
            ChargeStatus::SUCCEEDED,
            ChargeStatus::VOID,
        ])->orderByDesc('id');

        return Charge::collection($charges->paginate());
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\Customer $customer
     *
     * @return \App\Http\Resources\Business\Customer
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function update(Request $request, BusinessModel $business, CustomerModel $customer)
    {
        Gate::inspect('update', $business)->authorize();

        $customer = CustomerRepository::update($request, $customer);

        return new Customer($customer);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Business $business
     * @param \App\Business\Customer $customer
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function destroy(BusinessModel $business, CustomerModel $customer)
    {
        Gate::inspect('update', $business)->authorize();

        CustomerRepository::delete($customer);

        return Response::json([], 204);
    }

    public function createByEmail(CreateCustomerByEmailRequest $request, BusinessModel $business, CustomerManagerInterface $customerManager) 
    {
        try {
            $customer = $customerManager->getFindOrCreateByEmail($business, $request->input('email'));

            return Response::json(['id' => $customer->id], 201);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
