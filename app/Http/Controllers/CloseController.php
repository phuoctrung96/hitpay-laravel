<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Response;

class CloseController extends Controller
{
    public function __invoke()
    {
        return Response::view('close');
    }
}
