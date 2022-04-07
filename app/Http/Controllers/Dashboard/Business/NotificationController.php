<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Business;
use App\Business\Image as ImageModel;
use App\Enumerations\Image\Size;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;

class NotificationController extends Controller
{
    /**
     * BasicDetailController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request, Business $business){
        Gate::inspect('update', $business)->authorize();

        $business->load('subscribedEvents');

        return Response::view('dashboard.business.notifications.index', compact('business'));
    }

    public function update(Request $request, Business $business)
    {
        Gate::inspect('update', $business)->authorize();

        $data = $request->validate([
            'daily_collection@email' => 'required|bool',
            'daily_collection@push_notification' => 'required|bool',
            'daily_payout@email' => 'required|bool',
            'new_order@email' => 'required|bool',
            'new_order@push_notification' => 'required|bool',
            'pending_order@email' => 'required|bool',
            'pending_order@push_notification' => 'required|bool',
            'incoming_payment@email' => 'required|bool',
            'customer_receipt@email' => 'required|bool',
        ]);

        $business_id = $business->getKey();

        foreach ($data as $index => $value) {
            if ($value) {
                [$event, $channel] = explode('@', $index);

                $eventSubscriptions[] = compact('business_id', 'event', 'channel');
            }
        }

        DB::beginTransaction();

        $business->subscribedEvents()->delete();

        if (isset($eventSubscriptions)) {
            $business->subscribedEvents()->insert($eventSubscriptions);

            $subscribedEvents = $business->subscribedEvents;
        }

        DB::commit();

        return Response::json($subscribedEvents ?? []);
    }
}
