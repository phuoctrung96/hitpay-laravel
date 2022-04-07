<?php

namespace App\Http\Controllers\Admin;

use App\Business;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\Business\ComplianceRiskLevel;
use App\Enumerations\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Jobs\SendExportedBusinessesToAdmin;
use App\Jobs\SendExportedChargesToAdmin;
use App\Notifications\Business\NotifyUpdatedStatusVerification;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;

class BusinessController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role');
    }

    public function index(Request $request)
    {
        $paginator = Business::with('paymentProviders', 'verifications','complianceNotes')->join('users', 'users.id', 'businesses.user_id', 'wallets')->with([
            'owner' => function (Relation $query) {
                $query->withTrashed();
            },
        ]);

        $verificationStatus = $request->get('verification_status');
        $verification_type = $request->get('verification_type') ?? '';
        $risk_level = $request->get('risk_level') ?? '';

        if ($risk_level === ComplianceRiskLevel::LOW_RISK) {
            $paginator->whereHas('complianceNotes', function($q) {
                $q->where('risk_level', ComplianceRiskLevel::LOW_RISK);
            });
        } elseif ($risk_level === ComplianceRiskLevel::MEDIUM_RISK) {
            $paginator->whereHas('complianceNotes', function($q) {
                $q->where('risk_level', ComplianceRiskLevel::MEDIUM_RISK);
            });
        } elseif ($risk_level === ComplianceRiskLevel::HIGH_RISK) {
            $paginator->whereHas('complianceNotes', function($q) {
                $q->where('risk_level', ComplianceRiskLevel::HIGH_RISK);
            });
        }

        if ($verificationStatus === 'myinfo_verified') {
            $paginator->where('verified_wit_my_info_sg', true);
        } elseif ($verificationStatus === 'myinfo_unverified') {
            $paginator->where('verified_wit_my_info_sg', false);
        }elseif ($verificationStatus === 'pending'){
            $paginator->whereHas('verifications', function($q) {
                $q->where('status', 'pending');
            });
        }

        if ($verification_type === 'manual') {
            $paginator->whereHas('verifications', function($q) {
                $q->whereNull('my_info_data');
            });
        } elseif ($verification_type === 'myinfo') {
            $paginator->whereHas('verifications', function($q) {
                $q->whereNotNull('my_info_data');
            });
        }

        $keywords = $request->get('keywords');

        if ($keywords) {
            if (Str::isUuid($keywords)) {
                $paginator->where('businesses.id', $keywords);
            } elseif (is_numeric($keywords)) {
                $paginator->where('businesses.phone_number', 'LIKE', '%' . $keywords . '%');
            } else {
                $keywords = is_array($keywords) ? $keywords : explode(' ', $keywords);
                $keywords = array_map(function ($value) {
                    return trim($value);
                }, $keywords);
                $keywords = array_filter($keywords);
                $keywords = array_unique($keywords);

                if (count($keywords)) {
                    foreach ($keywords as $keyword) {
                        $paginator->where('businesses.name', 'like', '%'.$keyword.'%')
                            ->orWhere('businesses.email', 'like', '%'.$keyword.'%')
                            ->orWhere('users.email', 'like', '%'.$keyword.'%');
                    }
                }
            }
        }

        $paginator = $paginator->orderByDesc('id')->paginate();

        if ($keywords) {
            $paginator->appends('keywords', $request->get('keywords'));
        }

        if ($verificationStatus) {
            $paginator->appends('verification_status', $verificationStatus);
        }


        return Response::view('admin.business.business-index', compact('paginator', 'risk_level', 'verification_type'));
    }

    public function show(Business $business)
    {
        $business->load('client', 'gatewayProviders', 'wallets');
        $business->load([
            'owner' => function (Relation $query) {
                $query->withTrashed();
            },
        ]);
        $business->load([
            'paymentProviders' => function (HasMany $provider) {
                $provider->with('rates');
            },
        ]);

        return Response::view('admin.business.business-show', compact('business'));
    }

    public function edit(Business $business)
    {
        $business->load('client', 'gatewayProviders');
        $business->load([
            'owner' => function (Relation $query) {
                $query->withTrashed();
            },
        ]);

        return Response::view('admin.business.business-edit', compact('business'));
    }

    public function update(Request $request, Business $business)
    {
        $data = $request->validate([
            'owner_email' => [
                'required',
                'string',
                'email',
                Rule::unique('users', 'email')->ignore($business->user_id),
                'max:255',
            ],
            'business_email' => [
                'nullable',
                'string',
                'email',
                'max:255',
            ],
            'business_phone_number' => [
                'nullable',
                'digits_between:8,15',
            ],
            'business_verification' => 'json'
        ]);

        $owner = $business->owner;

        if (!$owner) {
            throw ValidationException::withMessages([
                'owner_email' => 'The owner of the business does\'t exists.',
            ]);
        }

        $owner->email = $data['owner_email'];
        $owner->save();

        $business->email = $data['business_email'] ?? null;
        $business->phone_number = $data['business_phone_number'] ?? null;
        $business->save();

        return Response::redirectToRoute('admin.business.show', $business->getKey())
            ->with('success', 'Business information has been updated successfully.');
    }

    public function export(Request $request)
    {
        SendExportedBusinessesToAdmin::dispatch($request->user());

        return back();
    }

    public function downloadVerifyDoc(Request $request){
        if ($file_path = $request->file_path) {
            $file_path = str_replace('_', '/', $file_path);
            $storageDefaultDisk = Storage::getDefaultDriver();

            if (!Storage::disk($storageDefaultDisk)->has($file_path))
                App::abort(404);

            return Storage::disk($storageDefaultDisk)->download($file_path);
        }
    }

    public function reject(Business $business){
        $business->update(['verified_wit_my_info_sg' => 0]);
        $verification = $business->verifications()->latest()->first();
        $verification->delete();

        $business->notify(new NotifyUpdatedStatusVerification($status = 'Rejected'));

        return redirect()->back();
    }

    public function verify(Business $business){
        $business->update(['verified_wit_my_info_sg' => 1]);
        $verification = $business->verifications()->latest()->first();
        $verification->update([
            'status' => VerificationStatus::MANUAL_VERIFIED,
            'verified_at' => $verification->freshTimestamp()
        ]);

        $business->notify(new NotifyUpdatedStatusVerification($status = 'Approved'));

        return redirect()->back();
    }

    public function delete(Request $request, Business $business){

        if($request->with == 'owner'){
            $business->owner->email = 'delete_'.time().'_'.$business->owner->email;
            $business->owner->save();

            $business->owner->delete();
        }

        $apiKey = $business->apiKeys->first();
        $apiKey->api_key = 'deleted_'.$apiKey->api_key;
        $apiKey->save();

        $business->delete();

        return Response::redirectToRoute('admin.business.index');
    }

    public function updateCompliance(Request $request, Business $business){

        $business->complianceNotes()->updateOrCreate(['business_id' => $business->id],[
            'risk_level' => $request->risk_level,
            'compliance_notes' => $request->compliance_notes ?? null
        ]);

        return redirect()->back();
    }
}
