<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Response;

class HomeController extends Controller
{
    /**
     * Get the basic information.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        return Response::json([
            'app_name' => Config::get('app.name'),
        ]);
    }
}
