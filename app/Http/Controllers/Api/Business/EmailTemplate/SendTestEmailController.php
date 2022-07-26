<?php

namespace App\Http\Controllers\Api\Business\EmailTemplate;

use App\Actions\Business\EmailTemplates\SendEmailTest;
use App\Business as BusinessModel;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;

class SendTestEmailController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * @param Request $request
     * @param BusinessModel $business
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function store(Request $request, BusinessModel $business): \Illuminate\Http\JsonResponse
    {
        Gate::inspect('update', $business)->authorize();

        $status = SendEmailTest::withBusiness($business)->data($request->post())->process();

        return Response::json(['status' => $status]);
    }
}
