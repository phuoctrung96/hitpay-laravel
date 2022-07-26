<?php

namespace App\Http\Controllers\Api\Business\EmailTemplate;

use App\Actions\Business\EmailTemplates\SetDefault;
use App\Business as BusinessModel;
use App\Http\Controllers\Controller;
use App\Http\Resources\Business\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ResetDefaultController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * @param Request $request
     * @param BusinessModel $business
     * @return EmailTemplate
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(Request $request, BusinessModel $business): EmailTemplate
    {
        Gate::inspect('update', $business)->authorize();

        $emailTemplate = SetDefault::withBusiness($business)->data($request->post())->process();

        return new EmailTemplate($emailTemplate);
    }
}
