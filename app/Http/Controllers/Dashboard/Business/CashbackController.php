<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Business;
use App\Enumerations\Business\PluginProvider;
use App\Http\Controllers\Controller;
use App\Manager\BusinessManagerInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CashbackController extends Controller
{
    /**
     * Cashback constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @param Request $request
     * @param Business $business
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Business $business)
    {
        $paginator = $business->cashbacks()->paginate(25);

        return Response::view('dashboard.business.cashback.index', compact('business', 'paginator'));
    }

    /**
     * @param Business $business
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function create( Business $business, BusinessManagerInterface $businessManager)
    {
        Gate::inspect('manage', $business)->authorize();
//        for now we restrict cashbacks only for pay now
//        $paymentMethods = $businessManager->getDefaultBusinessPaymentMethods($business, null);

        $paymentMethods = ['paynow_online' => 'PayNow'];
        $channels = PluginProvider::getAll(true, true);

        $fees['cashback_admin_fee'] = Business\Cashback::$cashback_admin_fee;

        return Response::view('dashboard.business.cashback.form', compact('business','paymentMethods', 'channels', 'fees'));
    }

    /**
     * @param Request $request
     * @param Business $business
     * @return \Illuminate\Http\JsonResponse
     * @throws ValidationException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function store(Request $request, Business $business)
    {
        Gate::inspect('manage', $business)->authorize();

        $cashbackID = $request->get('id');

        $requestData = $this->validate($request, [
            'fixed_amount' => 'required|decimal:0,2',
            'percentage' => 'required|decimal:0,2',
            'minimum_order_amount' => 'required|decimal:0,2',
            'maximum_cashback' => 'required|decimal:0,2',
            'channel' => 'required|string',
            'payment_provider_charge_type' => 'required|string',
            'ends_at' => 'nullable|string',
            ]);
        try {
            $requestData['fixed_amount'] = getRealAmountForCurrency($business->currency, $requestData['fixed_amount']);
            $requestData['minimum_order_amount'] = getRealAmountForCurrency($business->currency, $requestData['minimum_order_amount']);
            $requestData['maximum_cashback'] = getRealAmountForCurrency($business->currency, $requestData['maximum_cashback']);
            $requestData['name'] = "PayNow Cashback";
            if ($requestData['ends_at'])
                $requestData['ends_at'] = Carbon::parse($requestData['ends_at'])->format('Y-m-d 23:59:00');

            DB::beginTransaction();
            if(isset($cashbackID))
            {
                $cashback = $business->cashbacks()->find($cashbackID);
                Log::info('Updating cashback '. $cashbackID. '. Old fixed value: '. $cashback->fixed_amount. ', new: '. $requestData['fixed_amount']. '. Old percent: '. $cashback->percentage.', new: '. $requestData['percentage']);
                $cashback->update($requestData);
            }
            else {
                $business->cashbacks()->create($requestData);
            }
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
        Session::flash('success_message', !isset($cashbackID)? 'The cashback has been created.'
        :'Successfully updated');
        return Response::json([
            'redirect_url' => URL::route('dashboard.business.cashback.index', [
                $business->getKey(),
            ]),
        ]);
    }

    public function edit(Business $business, Business\Cashback $cashback)
    {
        if (!isset($cashback->id))
        {
            App::abort(404);
        }

        $paymentMethods = ['paynow_online' => 'PayNow'];
        $channels = PluginProvider::getAll(true, true);
        $fees['cashback_admin_fee'] = Business\Cashback::$cashback_admin_fee;

        return Response::view('dashboard.business.cashback.form', compact('business', 'cashback', 'paymentMethods', 'channels', 'fees'));
    }

    public function delete(Business $business, Business\Cashback $cashback)
    {
        if (!isset($cashback->id))
        {
            App::abort(404);
        }
        try {
            $cashback->delete();
        }
        catch (\Exception $exception)
        {
            throw $exception;
        }
        Session::flash('success_message', 'Successfully deleted');
        return redirect()->back();
    }

    public function changeState(Business $business, Business\Cashback $cashback, Request $request){
        $cashback->enabled = $request->enabled;
        $cashback->save();

        Session::flash('success_message', 'Successfully updated');
        return redirect()->back();
    }
}
