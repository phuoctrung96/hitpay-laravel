<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Actions\Business\Settings\BankAccount\Destroy;
use App\Actions\Business\Settings\BankAccount\SetAsDefaultForHitPayPayout;
use App\Actions\Business\Settings\BankAccount\SetAsDefaultForStripePayout;
use App\Actions\Business\Settings\BankAccount\Store;
use App\Actions\Business\Settings\BankAccount\Update;
use App\Actions\Exceptions\BadRequest;
use App\Business;
use App\Http\Controllers\Controller;
use App\Http\Resources\Business\BankAccount as BankAccountResource;
use App\Models\Business\BankAccount;
use Illuminate\Http;
use Illuminate\Support\Facades;

class EmailTemplateController extends Controller
{
    /**
     * BankAccountController Constructor
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the homepage of the settings for bank accounts.
     *
     * @param  \App\Business  $business
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Business $business) : Http\Response
    {
        Facades\Gate::inspect('view', $business)->authorize();

        return Facades\Response::view('dashboard.business.settings.email-templates.home', [
            'business' => $business,
            'bankAccounts' => $business->bankAccounts()->get(),
        ]);
    }
}
