<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Business;
use App\Manager\BusinessManagerInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

class CustomisationController extends Controller
{
    /**
     * CustomisationController constructor.
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
    public function index(Business $business, BusinessManagerInterface $businessManager)
    {
      Gate::inspect('view', $business)->authorize();

      $customisation = $business->checkoutCustomisation();

      $customisation->all_methods = array_keys($businessManager->getByBusinessAvailablePaymentMethods($business, null, true));

      if (!$customisation->payment_order) {
        $customisation->payment_order = $customisation->all_methods;
      }

      return Response::view('dashboard.business.customisation.index', compact('business', 'customisation'));
    }

    public function patch(Request $request, Business $business, BusinessManagerInterface $businessManager) {
      Gate::inspect('update', $business)->authorize();

      $validatedData = $request->validate([
        'customColor' => ['string', 'max:7'],
        'theme' => [ Rule::in(['hitpay', 'custom', 'light']) ],
        'payment_order.*' => [ Rule::in(array_keys($businessManager->getByBusinessAvailablePaymentMethods($business, null, true))) ],
        'method_rules' => ['json']
      ]);

      $business->updateCustomisation($validatedData);
    }
}
