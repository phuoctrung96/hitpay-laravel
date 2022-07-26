<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Business as BusinessModel;
use App\Business\Customer as CustomerModel;
use App\Enumerations\AllCountryCode;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\Business\InvoiceStatus;
use App\Enumerations\Business\ShippingCalculation;
use App\Exports\CustomerFeedTemplate;
use App\Http\Controllers\Controller;
use App\Http\Resources\Business\Customer;
use App\Jobs\SendExportedCustomers;
use App\Logics\Business\CustomerRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Maatwebsite\Excel\Facades\Excel;

class CustomerController extends Controller
{
    /**
     * CustomerController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request, BusinessModel $business)
    {
        Gate::inspect('view', $business)->authorize();

        $paginator = $business->customers();

        $keywords = explode(' ', $request->get('keywords'));
        $keywords = array_map(function ($value) {
            return trim($value);
        }, $keywords);
        $keywords = array_filter($keywords);
        $keywords = array_unique($keywords);

        if (count($keywords) > 0) {
            $paginator->select($paginator->qualifyColumn('*'));
            $paginator->where(function (Builder $query) use ($keywords) {
                for ( $i=0; $i<3; $i++ ) {
                    if (count($keywords) > $i) {
                        $query->orWhere($query->qualifyColumn('name'), 'LIKE', '%' . $keywords[$i] . '%');
                        $query->orWhere($query->qualifyColumn('email'), 'LIKE', '%' . $keywords[$i] . '%');
                    }
                }
            });
        }

        $paginator = $paginator->paginate(10);

        return Response::view('dashboard.business.customer.index', compact('business', 'paginator'));
    }

    /**
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \ReflectionException
     */
    public function create(BusinessModel $business)
    {
        Gate::inspect('canManageCustomer', $business)->authorize();

        $data = $this->formData();

        return Response::view('dashboard.business.customer.form', compact('business', 'data'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function export(Request $request, BusinessModel $business)
    {
        Gate::inspect('view', $business)->authorize();

        SendExportedCustomers::dispatch($business);

        return Response::json([
            'success' => true,
        ]);
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
        Gate::inspect('canManageCustomer', $business)->authorize();

        $customer = CustomerRepository::store($request, $business, false);

        Session::flash('success_message', 'The customer \''.($customer->name ?? $customer->email)
            .'\' has been created successfully.');

        return Response::json([
            'redirect_url' => URL::route('dashboard.business.customer.show', [
                $business->getKey(),
                $customer->getKey(),
            ]),
            'customer' => $customer
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Business $business
     * @param \App\Business\Customer $customer
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(BusinessModel $business, CustomerModel $customer)
    {
        Gate::inspect('view', $business)->authorize();

        $paginator = $customer->charges()->whereIn('status', [
            ChargeStatus::SUCCEEDED,
            ChargeStatus::REFUNDED,
            ChargeStatus::VOID,
        ])->orderByDesc('id')->paginate();

        return Response::view('dashboard.business.customer.show', compact('business', 'customer', 'paginator'));
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Business $business
     * @param \App\Business\Customer $customer
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \ReflectionException
     */
    public function edit(BusinessModel $business, CustomerModel $customer)
    {
        Gate::inspect('view', $business)->authorize();

        $data = $this->formData();

        return Response::view('dashboard.business.customer.form', compact('business', 'customer', 'data'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\Customer $customer
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function update(Request $request, BusinessModel $business, CustomerModel $customer)
    {
        Gate::inspect('canManageCustomer', $business)->authorize();

        $customer = CustomerRepository::update($request, $customer, false);

        Session::flash('success_message', 'The customer \''.($customer->name ?? $customer->email)
            .'\' has been updated successfully.');

        return Response::json([
            'redirect_url' => URL::route('dashboard.business.customer.show', [
                $business->getKey(),
                $customer->getKey(),
            ]),
        ]);
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
        Gate::inspect('canManageCustomer', $business)->authorize();

        $customerName = $customer->name ?? $customer->email;

        CustomerRepository::delete($customer);

        Session::flash('success_message', 'The customer \''.$customerName.'\' has been deleted successfully.');

        return Response::json([
            'redirect_url' => URL::route('dashboard.business.customer.index', $business->getKey()),
        ]);
    }

    /**
     * Remove bulk resource from storage.
     *
     * @param Request $request
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function bulkDestroy(Request $request, BusinessModel $business)
    {
        Gate::inspect('canManageCustomer', $business)->authorize();

        $request->validate([
            'customer_ids' => 'required|array',
            'customer_ids.*' => 'exists:business_customers,id',
        ]);

        if ($params = $request->customer_ids) {
            for ($i=0; $i<count($params); $i++) {
                if ($customer = CustomerModel::find($params[$i])) {
                    CustomerRepository::delete($customer);
                }
            }
        }

        Session::flash('success_message', 'Customers deleted successfully.');

        return Response::json([
            'redirect_url' => URL::route('dashboard.business.customer.index', $business->getKey()),
        ]);
    }

    /**
     * @return mixed
     * @throws \ReflectionException
     */
    private function formData()
    {
        $countries = new Collection;

        foreach (AllCountryCode::listConstants() as $value) {
            if (Lang::has('misc.country.'.$value)) {
                $name = Lang::get('misc.country.'.$value);
            } else {
                $name = $value;
            }

            $countries->add([
                'code' => $value,
                'name' => $name,
            ]);
        }

        $data['countries'] = $countries->sortBy('name')->values()->toArray();

        return $data;
    }

    /**
     * @param Request $request
     * @param Business $business
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function createInBulk(Request $request, BusinessModel $business){
        Gate::inspect('manage', $business)->authorize();

        return Response::view('dashboard.business.customer.bulk', compact('business'));
    }

    /**
     * @param Request $request
     * @param Business $business
     * @return Excel
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function downloadFeedTemplate(Request $request, BusinessModel $business){
        Gate::inspect('manage', $business)->authorize();
        $fileName = config('app.name') . "-customer-feed.csv";
        return Excel::download(new CustomerFeedTemplate, $fileName, \Maatwebsite\Excel\Excel::CSV)->send();
    }

    /**
     * @param Request $request
     * @param Business $business
     * @return JsonResponse
     */
    public
    function uploadFeedFile(Request $request, BusinessModel $business)
    {
        $request->validate([
            'file' => 'required',
        ]);
        $folderName = $business->getKey() . '/customer-feed-templates';
        $fileName = $business->getKey() . '-' . time() . '-customer_feed_template.csv';
        $path = $request->file('file')->storeAs($folderName, $fileName);

        // Artisan::queue('proceed:customerFeed --business_id=' . $business->getKey() . ' --file_path=' . $path);

        Session::flash('success_message', 'We  will start to upload shortly and email you the result.');
        return Response::json([
            'redirect_url' => URL::route('dashboard.business.customer.index', $business->getKey()),
        ]);
    }
}
