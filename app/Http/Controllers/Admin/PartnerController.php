<?php

namespace App\Http\Controllers\Admin;

use App\Business;
use App\Business\PaymentProvider;
use App\Enumerations\BusinessPartnerStatus;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Partners\ExportRequest;
use App\Jobs\SendExportedPartnersToAdmin;
use App\Notifications\NewPartnerRegistration;
use App\Notifications\PartnerChangeStatusForAdminNotification;
use App\Notifications\PartnerChangeStatusNotification;
use App\Services\Rates\CustomRatesService;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Response;

class PartnerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role');
    }

    public function index(Request $request)
    {
        $searchQuery = $request->input('query');
        $pendingPartners = User::query()
            ->when(!empty($searchQuery), function (Builder $builder) use($searchQuery) {
                return $builder->search($searchQuery);
            })
            ->whereHas('businessPartner', function ($query) {
                return $query
                    ->where('status', BusinessPartnerStatus::PENDING);
            })
            ->orderByDesc('id')
            ->paginate();

        $approvedPartners = User::query()
            ->when(!empty($searchQuery), function (Builder $builder) use ($searchQuery) {
                return $builder->search($searchQuery);
            })
            ->whereHas('businessPartner', function ($query) {
                return $query
                    ->where('status', BusinessPartnerStatus::ACCEPTED);
            })
            ->orderByDesc('id')
            ->paginate();

        $rejectedPartners = User::query()
            ->when(!empty($searchQuery), function (Builder $builder) use ($searchQuery) {
                return $builder->search($searchQuery);
            })
            ->whereHas('businessPartner', function ($query) {
                return $query
                    ->where('status', BusinessPartnerStatus::REJECTED);
            })
            ->orderByDesc('id')
            ->paginate();

        return Response::view('admin.partner.index', compact('pendingPartners', 'approvedPartners', 'rejectedPartners'));
    }



    public function export(ExportRequest $request)
    {
        SendExportedPartnersToAdmin::dispatch($request->input('starts_at'), $request->input('ends_at'));

        return Response::json([
            'success' => true,
        ]);
    }

    public function approve($id)
    {
        $user = User::query()
            ->whereHas('businessPartner', function ($query) {
                return $query->where('status', BusinessPartnerStatus::PENDING);
            })
            ->findOrFail($id);
        $user->businessPartner->status = BusinessPartnerStatus::ACCEPTED;
        $user->businessPartner->save();

        foreach (User::superAdmins() as $superAdmin) {
            $superAdmin->notify(new PartnerChangeStatusForAdminNotification($user));
        }
        $user->notify(new PartnerChangeStatusNotification());

        return back();
    }

    public function reject($id)
    {
        $user = User::query()
            ->whereHas('businessPartner', function ($query) {
                return $query->where('status', BusinessPartnerStatus::PENDING);
            })
            ->findOrFail($id);
        $user->businessPartner->status = BusinessPartnerStatus::REJECTED;
        $user->businessPartner->save();

        User::superAdmins()->each(function(User $admin) use ($user) {
            $admin->notify(new PartnerChangeStatusForAdminNotification($user));
        });
        $user->notify(new PartnerChangeStatusNotification());

        return back();
    }

    public function show($id)
    {
        $user = User::query()
            ->whereHas('businessPartner', function ($query) {
                return $query->where('status', BusinessPartnerStatus::ACCEPTED);
            })
            ->findOrFail($id);
        $partner = $user->businessPartner;

        return view('admin.partner.show', compact('user', 'partner'));
    }

    public function update(Request $request, $id)
    {
        $user = User::query()
            ->whereHas('businessPartner', function ($query) {
                return $query->where('status', BusinessPartnerStatus::ACCEPTED);
            })
            ->findOrFail($id);

        $user->businessPartner->commission = $request->input('commission');
        $user->businessPartner->save();

        return back();
    }

    public function showCustomRatesForm($id)
    {
        $user = User::query()
            ->whereHas('businessPartner', function ($query) {
                return $query->where('status', BusinessPartnerStatus::ACCEPTED);
            })
            ->findOrFail($id);

        return view('admin.partner.set-custom-rates', compact('user'));
    }

    public function saveCustomRates(Request $request, CustomRatesService $customRatesService, $id)
    {
        /** @var User $user */
        $user = User::query()
            ->whereHas('businessPartner', function ($query) {
                return $query->where('status', BusinessPartnerStatus::ACCEPTED);
            })
            ->findOrFail($id);

        foreach($user->businessPartner->businesses()->get() as $business) {
            foreach (array_keys($request->input('channel')) as $paymentProviderCode) {
                if ($paymentProviderCode === 'stripe') {
                    $paymentProviderCodeDB = $business->payment_provider;
                } elseif ($paymentProviderCode === 'paynow') {
                    $paymentProviderCodeDB = PaymentProviderEnum::DBS_SINGAPORE;
                } else {
                    App::abort(404);
                }

                $paymentProvider = $business->paymentProviders()
                    ->with('rates')
                    ->where('payment_provider', $paymentProviderCodeDB)
                    ->first();
                if(!$paymentProvider) {
                    continue;
                }

                $customRatesService->updateRates(
                    $paymentProvider,
                    $request->input('method')[$paymentProviderCode],
                    $request->input('channel')[$paymentProviderCode],
                    $request->input('percentage')[$paymentProviderCode],
                    $request->input('fixed_amount')[$paymentProviderCode]
                );
            }
        }

        return redirect(route('admin.partner.show', $user));
    }

    public function savePartnerPricing(Request $request, $id)
    {
        /** @var User $user */
        $user = User::query()
            ->whereHas('businessPartner', function ($query) {
                return $query->where('status', BusinessPartnerStatus::ACCEPTED);
            })
            ->findOrFail($id);

        $pricingList = array_filter($request->input('pricing'), function ($pricing) {
            return !empty($pricing['paynow_percentage']) || !empty($pricing['paynow_fixed_amount'])
            || !empty($pricing['stripe_percentage']) || !empty($pricing['stripe_fixed_amount']);
        });

        $user->businessPartner->update(['pricing' => array_values($pricingList)]);

        return back();
    }

    public function bulkMapBusinesses(Request $request, $id)
    {
        $this->validate($request, [
            'file' => 'required|mimes:csv,txt'
        ]);

        /** @var User $user */
        $user = User::query()
            ->whereHas('businessPartner', function ($query) {
                return $query->where('status', BusinessPartnerStatus::ACCEPTED);
            })
            ->findOrFail($id);

        $file = $request->file('file');
        $lines = explode(PHP_EOL, file_get_contents($file));
        foreach ($lines as $i => $line) {
            if(!$i || !$business = Business::find($line)) {
                continue;
            }
            $user->businessPartner->businesses()->syncWithoutDetaching([$business->id]);
        }

        session()->flash('success_map', 'Businesses were successfully mapped to partner');

        return back();
    }
}
