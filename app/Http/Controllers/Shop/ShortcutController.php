<?php

namespace App\Http\Controllers\Shop;

use App\Shortcut;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

class ShortcutController extends Controller
{
    public function redirect(string $id)
    {
        $shortcut = Shortcut::findOrFail($id);

        if (!Route::has($shortcut->route_name)) {
            App::abort(404);
        }

        return Response::redirectToRoute($shortcut->route_name, $shortcut->parameters);
    }
}
