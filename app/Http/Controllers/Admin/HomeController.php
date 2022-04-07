<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Response;

class HomeController extends Controller
{
    /**
     * HomeController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role');
    }

    /**
     * Show homepage.
     *
     * @return \Illuminate\Http\Response
     */
    public function showHomepage()
    {
        return Response::view('admin.home');
    }
}
