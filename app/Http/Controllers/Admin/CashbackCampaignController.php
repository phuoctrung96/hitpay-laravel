<?php

namespace App\Http\Controllers\Admin;

use App\Business\CashbackCampaign;
use App\Business\Charge;
use App\Enumerations\Business\PaymentMethodType;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class CashbackCampaignController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role');
    }

    public function index(Request $request)
    {
        $paginator = CashbackCampaign::with('campaignBusiness')->paginate();

        return Response::view('admin.cashback-campaign.index', compact('paginator'));
    }

    public function create(Request $request)
    {
        $payment_methods = PaymentMethodType::getPaymentMethods();

        return Response::view('admin.cashback-campaign.form', compact('payment_methods'));
    }

    public function store(Request $request, CashbackCampaign $campaign)
    {
        $requestData = $this->validate($request, [
            'campaign.campaign_business_id' => 'required|exists:App\Business,id',
            'campaign.fund' => 'required|decimal:0,2',
            'campaign.name' => 'required|string',
            'campaign.status' => 'required|boolean',
            'campaign.payment_method' => 'required|string',
            'campaign.payment_sender' => 'nullable|string',
            'rules' => 'required|array'
        ]);

        try {
            $requestData['campaign']['fund'] = getRealAmountForCurrency('sgd', $requestData['campaign']['fund']);

            DB::beginTransaction();
            if (isset($campaign->id)) {
                $campaign->update($requestData['campaign']);
                $campaign->rules()->delete();
                $campaign->rules()->createMany($requestData['rules']);
            } else {
                $newCampaign = CashbackCampaign::create($requestData['campaign']);
                $newCampaign->rules()->createMany($requestData['rules']);
            }
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
        Session::flash('success_message', !isset($campaign->id) ? 'The campaign has been created.'
            : 'Successfully updated');
        return Response::json([
            'redirect_url' => URL::route('admin.campaigns.index'),
        ]);
    }

    public function edit(Request $request, CashbackCampaign $campaign)
    {
        $campaign->fund = getReadableAmountByCurrency('sgd', $campaign->fund);
        $campaign->load('rules');

        $payment_methods = PaymentMethodType::getPaymentMethods();

        return Response::view('admin.cashback-campaign.form', compact('campaign', 'payment_methods'));
    }

    public function delete(Request $request, CashbackCampaign $campaign)
    {
        try {
            $campaign->delete();
        }
        catch (\Exception $exception)
        {
            throw $exception;
        }

        Session::flash('success_message', 'Successfully deleted.');
        return Response::json([
            'redirect_url' => URL::route('admin.campaigns.index'),
        ]);
    }

    public function addRule(Request $request, CashbackCampaign $campaign){

    }

}
