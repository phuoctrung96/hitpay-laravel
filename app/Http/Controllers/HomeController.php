<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Response;

class HomeController extends Controller
{
    public function showHomepage()
    {
        return Response::redirectTo('https://www.hitpayapp.com');
    }
}
