<?php

namespace App\Http\Controllers\Api;

use App\Business as BusinessModel;
use App\Business\Charge;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\Permission;
use App\Http\Controllers\Controller;
use App\Http\Resources\Business;
use App\Http\Resources\Business\Charge as ChargeResource;
use App\Logics\BusinessRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Response;

class BusinessController extends Controller
{
    /**
     * BusinessController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($request->get('list_all') && $user->role && $user->role->hasPermission(Permission::ALL)) {
            $businesses = BusinessModel::query()->with('owner');

            $criteria = $request->get('criteria');

            if ($criteria === 'only_trashed') {
                $businesses->onlyTrashed();
            } elseif ($criteria === 'with_trashed') {
                $businesses->withTrashed();
            }
        } else {
            $businesses = $user->businessesOwned();
        }

        return Business::collection($businesses->get());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \App\Http\Resources\Business
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function store(Request $request)
    {
        Gate::inspect('store', BusinessModel::class)->authorize();

        $business = BusinessRepository::store($request, Auth::user());

        return new Business($business);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Business $business
     *
     * @return \App\Http\Resources\Business
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(BusinessModel $business)
    {
        Gate::inspect('view', $business)->authorize();

        return new Business($business);
    }

    public function getDailyReport(Request $request, BusinessModel $business)
    {
        $today = Carbon::today();

        $charge = $business->charges()->where('status', ChargeStatus::SUCCEEDED)
            ->orderByDesc('closed_at')->first();

        if ($charge instanceof Charge) {
            $charge = (new ChargeResource($charge))->toArray($request);
        }

        $cache = [
            'collection' => $business->charges()->selectRaw('currency, sum(amount) as sum')
                ->where('status', ChargeStatus::SUCCEEDED)
                ->whereDate('closed_at', $today)
                ->groupBy('currency')
                ->pluck('sum', 'currency', 'business_id')
                ->toArray(),
            'last_transaction' => $charge,
        ];

        foreach ($cache['collection'] as $code => $amount) {
            $data[] = [
                'currency' => $code,
                'name' => Lang::get('misc.currency.'.$code),
                'amount' => number_format(getReadableAmountByCurrency($code, $amount), 2),
            ];
        }

        return Response::json([
            'collection' => $data ?? [],
            'last_transaction' => $cache['last_transaction'] ?? null,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \App\Http\Resources\Business
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function update(Request $request, BusinessModel $business)
    {
        Gate::inspect('update', $business)->authorize();

        BusinessRepository::update($request, $business);

        return new Business($business);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \App\Http\Resources\Business
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function updateIdentifier(Request $request, BusinessModel $business)
    {
        Gate::inspect('update', $business)->authorize();

        BusinessRepository::updateIdentifier($request, $business);

        return new Business($business);
    }
}
