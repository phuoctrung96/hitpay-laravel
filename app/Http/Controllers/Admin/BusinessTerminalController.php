<?php

namespace App\Http\Controllers\Admin;

use App\Business;
use App\Http\Controllers\Controller;
use App\StripeTerminal;
use Exception;
use HitPay\Stripe\Charge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Stripe\Exception\InvalidRequestException;
use Stripe\Terminal\Location;
use Stripe\Terminal\Reader;

class BusinessTerminalController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role');
    }

    public function index(Business $business)
    {
        $paginator = $business->stripeTerminals()->orderByDesc('id')->paginate();

        return Response::view('admin.business.terminal-index', compact('business', 'paginator'));
    }

    public function create(Business $business)
    {
        if ($business->paymentProviders()->where('payment_provider', $business->payment_provider)->count() === 0) {
            App::abort(403, 'The business hasn\'t setup stripe.');
        }

        return Response::view('admin.business.terminal-create', compact('business'));
    }

    public function store(Business $business, Request $request)
    {
        if ($business->paymentProviders()->where('payment_provider', $business->payment_provider)->count() === 0) {
            return Response::redirectToRoute('admin.business.terminal.create', $business->getKey());
        }

        $data = $this->validate($request, [
            'registration_code' => 'required|string',
            'label' => 'required|string',
        ]);

        $business->load('stripeTerminalLocations');

        Charge::new('stripe_sg');

        $create = false;

        if ($business->stripeTerminalLocations->count() > 0) {
            foreach ($business->stripeTerminalLocations as $businessLocation) {
                try {
                    $stripeLocation = Location::retrieve($businessLocation->stripe_terminal_location_id);
                } catch (InvalidRequestException $exception) {
                }
            }

            if (!isset($stripeLocation)) {
                $create = true;
            }
        } else {
            $create = true;
        }

        if ($create) {
            $stripeLocation = Location::create([
                'display_name' => $business->getName(),
                'address' => [
                    'country' => $business->country,
                ],
            ]);

            DB::beginTransaction();

            try {
                $businessLocation = $business->stripeTerminalLocations()->create([
                    'name' => $stripeLocation->display_name,
                    'stripe_terminal_location_id' => $stripeLocation->id,
                    'data' => $stripeLocation->toArray(),
                ]);

                DB::commit();
            } catch (Exception $exception) {
                $stripeLocation->delete();

                throw $exception;
            }
        }

        try {
            $stripeReader = Reader::create([
                'registration_code' => $data['registration_code'],
                'label' => $data['label'],
                'location' => $stripeLocation->id,
            ]);
        } catch (InvalidRequestException $exception) {
            $response = $exception->getJsonBody();

            if (isset($response['error']['type']) && $response['error']['type'] === 'invalid_request_error') {
                throw ValidationException::withMessages([
                    'registration_code' => 'The registration code is invalid.',
                ]);
            }

            throw $exception;
        }

        DB::beginTransaction();

        try {
            /** @var \App\Business\StripeTerminalLocation $businessLocation */
            $businessTerminal = $businessLocation->terminals()->create([
                'name' => $stripeReader->label,
                'stripe_terminal_id' => $stripeReader->id,
                'device_type' => $stripeReader->device_type,
                'remark' => $stripeReader->device_sw_version,
                'data' => $stripeReader->toArray(),
            ]);

            DB::commit();
        } catch (Exception $exception) {
            $stripeLocation->delete();

            throw $exception;
        }

        Session::flash('success_message',
            'A new terminal \''.$businessTerminal->name.'\' has been added for '.$business->getName().'.');

        return Response::redirectToRoute('admin.business.terminal.index', [
            $business->getKey(),
        ]);
    }

    public function show(Business $business, StripeTerminal $terminal)
    {
        return Response::view('admin.business.terminal-show', compact('business', 'terminal'));
    }

    public function destroy(Business $business, StripeTerminal $terminal)
    {
        Charge::new('stripe_sg');

        try {
            Reader::retrieve($terminal->stripe_terminal_id)->delete();
        } catch (InvalidRequestException $exception) {
            if ($exception->getStripeCode() !== 'resource_missing') {
                throw $exception;
            }
        }

        $terminalName = $terminal->name;

        $terminal->delete();

        Session::flash('success_message',
            'A new terminal \''.$terminalName.'\' has been deleted from '.$business->getName().'.');

        return Response::redirectToRoute('admin.business.terminal.index', $business->getKey());
    }
}
