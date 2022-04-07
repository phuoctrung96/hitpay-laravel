<?php


namespace App\Http\Controllers\Dashboard;


use Illuminate\Support\Facades\Response;

class XeroLoginController
{
    public function index()
    {
        return Response::view('dashboard.authentication.xero-auth');
    }
}
