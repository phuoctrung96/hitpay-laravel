<?php

namespace App\Http\Controllers\Admin;

use App\Business;
use App\Enumerations\Business\ChargeStatus;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class BusinessPlatformController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role');
    }

    public function index(Request $request, Business $business)
    {
        $paginator = $business->platformCharges()->with([
            'business' => function (Relation $query) {
                $query->withTrashed();
            },
        ]);

        if ($request->has('keyword')) {
            $keyword = $request->get('keyword');
            if ($keyword) {
                $paginator->where('id', $keyword)
                    ->orWhere('business_target_id', $keyword)
                    ->orWhere('plugin_provider_reference', $keyword)
                    ->orWhere('customer_email', 'like', '%'.$keyword.'%');
            }
        }

        $paginator = $paginator->where('status', ChargeStatus::SUCCEEDED)->paginate();

        return Response::view('admin.business.platform-index', compact('business', 'paginator'));
    }

    public function enable(Request $request, Business $business)
    {
        $business->platform_enabled = true;
        $business->save();

        $request->session()->flash('success_message', 'Platform feature has been enabled for '.$business->getName());

        return Response::redirectToRoute('admin.business.platform.index', $business->getKey());
    }

    public function disable(Request $request, Business $business)
    {
        $business->platform_enabled = false;
        $business->save();

        $request->session()->flash('danger_message', 'Platform feature has been disabled for '.$business->getName());

        return Response::redirectToRoute('admin.business.platform.index', $business->getKey());
    }

    public function rekey(Request $request, Business $business)
    {
        if ($business->platform_enabled) {
            $business->platform_key = Str::lower(implode('-', [
                Str::orderedUuid()->toString(),
                str_pad(time(), 12, '0', STR_PAD_LEFT),
                Str::random(4),
                Str::random(4),
                Str::random(4),
            ]));
            $business->save();

            $request->session()->flash('success_message', 'Platform key has been regenerated for '.$business->getName());
        } else {
            $request->session()->flash('danger_message', $business->getName().' isn\'t enable platform yet.');
        }

        return Response::redirectToRoute('admin.business.platform.index', $business->getKey());
    }
}
