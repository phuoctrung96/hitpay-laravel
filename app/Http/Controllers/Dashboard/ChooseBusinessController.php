<?php


namespace App\Http\Controllers\Dashboard;


use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ChooseBusinessController extends Controller
{
    public function __invoke()
    {
        $businesses = Auth::user()->businesses;

        return view('dashboard.choose-dashboard', compact('businesses'));
    }
}
