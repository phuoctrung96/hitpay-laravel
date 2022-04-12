<?php

namespace App\Http\Controllers\Admin;

use App\Business;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class BusinessBankAccountController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role');
    }

    /**
     * @param Request $request
     * @param Business $business
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Business $business): \Illuminate\Http\Response
    {
        $paginator = $business->bankAccounts()->paginate();

        return Response::view('admin.business.bank-accounts.index', compact('business', 'paginator'));
    }
}
