<?php

namespace App\Http\Controllers\Api\Business\EmailTemplate;

use App\Actions\Business\EmailTemplates\Retrieve;
use App\Actions\Business\EmailTemplates\StoreDefault;
use App\Actions\Business\EmailTemplates\Update;
use App\Business as BusinessModel;
use App\Http\Controllers\Controller;
use App\Http\Resources\Business\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EmailTemplateController extends Controller
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
     * @throws \Exception
     */
    public function show(Request $request, BusinessModel $business): EmailTemplate
    {
        Gate::inspect('view', $business)->authorize();

        $emailTemplate = Retrieve::withBusiness($business)->process();

        return new EmailTemplate($emailTemplate);
    }

    /**
     * @param Request $request
     * @param BusinessModel $business
     * @return EmailTemplate
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function store(Request $request, BusinessModel $business): EmailTemplate
    {
        Gate::inspect('update', $business)->authorize();

        $emailTemplate = StoreDefault::withBusiness($business)->data($request->post())->process();

        return new EmailTemplate($emailTemplate);
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

        $emailTemplate = Update::withBusiness($business)->data($request->post())->process();

        return new EmailTemplate($emailTemplate);
    }
}
