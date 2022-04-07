<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\StripeTerminal;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class TerminalController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role');
    }

    public function index(Request $request)
    {
        $paginator = StripeTerminal::with([
            'location' => function (BelongsTo $query) {
                $query->with([
                    'business' => function (Relation $query) {
                        $query->withTrashed();
                    },
                ]);
            },
        ]);

        $paginator = $paginator->orderByDesc('id')->paginate();

        return Response::view('admin.terminal-index', compact('paginator'));
    }
}
