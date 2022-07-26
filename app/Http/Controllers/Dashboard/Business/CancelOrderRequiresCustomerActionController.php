<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Business;
use App\Business\Order;
use App\Enumerations\Business\OrderStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;

class CancelOrderRequiresCustomerActionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @param Request $request
     * @param Business $business
     * @param Order $order
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function __invoke(Request $request, Business $business, Order $order): \Illuminate\Http\RedirectResponse
    {
        Gate::inspect('operate', $business)->authorize();

        if (!$order->status == 'requires_customer_action') {
            App::abort(403, 'You can\'t cancel an order which is not requires customer action.');
        }

        $order->status = OrderStatus::CANCELED;

        $order->save();

        $order->notifyAboutStatusChanged('Your order has been cancelled', $order->isCompleted());

        Session::flash('success_message', 'The order has been canceled.');

        return Response::redirectToRoute('dashboard.business.order.show', [
            $business->getKey(),
            $order->getKey(),
        ]);
    }
}
