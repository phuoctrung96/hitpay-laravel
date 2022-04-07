<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Business;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
class TaxSettingController extends Controller
{
    /**
     * Discount constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @param Request $request
     * @param Business $business
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Business $business)
    {
        $paginator = $business->tax_settings()->paginate(25);
        return Response::view('dashboard.business.tax.index', compact('business','paginator'));
    }

    /**
     * @param Request $request
     * @param Business $business
     * @return \Illuminate\Http\JsonResponse
     * @throws ValidationException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function store(Request $request, Business $business)
    {
        Gate::inspect('manage', $business)->authorize();
        $requestData = $this->validate($request, [
            'name' => 'required|string|max:255',
            'rate' => 'required|decimal:0,2',
        ]);

        $result = null;

        try {

            DB::beginTransaction();
            $result = $business->tax_settings()->create($requestData);
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }

        if ($request->wantsJson()) {
            return Response::json([$result]);
        }

        Session::flash('success_message', 'The tax has been created.');
        return redirect()->route('dashboard.business.tax-setting.home', [
            $business->getKey(),
        ]);
    }

    public function update(Request $request, Business $business, Business\TaxSetting $tax)
    {
        $requestData = $this->validate($request, [
            'name' => 'required|string|max:255',
            'rate' => 'required|decimal:0,2',
        ]);
        $business->tax_settings()->where('id', $tax->id)->update($requestData);
        Session::flash('success_message', 'Successfully updated');
        return redirect()->route('dashboard.business.tax-setting.home', [
            $business->getKey(),
        ]);
    }

    public function edit(Business $business, Business\TaxSetting $tax)
    {
        if (!isset($tax->id))
        {
            App::abort(404);
        }
        return Response::view('dashboard.business.tax.edit', compact('business', 'tax'));
    }
    public function delete(Business $business, Business\TaxSetting $tax)
    {
        if (!isset($tax->id))
        {
            App::abort(404);
        }
        try {
            $tax->delete();
        }
        catch (\Exception $exception)
        {
            throw $exception;
        }
        Session::flash('success_message', 'Successfully deleted');
        return redirect()->back();
    }
}
