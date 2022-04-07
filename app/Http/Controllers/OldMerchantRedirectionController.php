<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Response;

class OldMerchantRedirectionController extends Controller
{
    public function redirect()
    {
        return Response::redirectToRoute('dashboard.home');
    }
}
