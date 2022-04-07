<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Business;
use App\Business as BusinessModel;
use App\Business\Image as ImageModel;
use App\Enumerations\Business\ImageGroup;
use App\Enumerations\Image\Size;
use App\Http\Controllers\Controller;
use App\Logics\Business\ChargeRepository;
use App\Logics\BusinessRepository;
use HitPay\Image\Processor as ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use function Psy\bin;

class BasicDetailController extends Controller
{
    /**
     * BasicDetailController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show homepage for editing business.
     *
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(BusinessModel $business)
    {
        Gate::inspect('update', $business)->authorize();

        $business->load('subscribedEvents');

        $logo = $business->logo()->first();

        $default_logo_url = '/hitpay/images/product.jpg';
        if ($logo instanceof ImageModel) {
            $logo_url = $logo->getUrl(Size::MEDIUM);
        }
        $data = [
            'default_logo_url' => $default_logo_url,
            'logo_url' => $logo_url ?? null,
        ];

        return Response::view('dashboard.business.edit', compact('business', 'data'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function uploadLogo(Request $request, BusinessModel $business)
    {
        $image = $this->validate($request, [
            'image' => [
                'required',
                'image',
            ],
        ])['image'];

        /**
         * @var \App\Business\Image $image
         */
        $image = DB::transaction(function () use ($business, $image) {
            $image = ImageProcessor::new($business, ImageGroup::LOGO, $image)
                ->setCaption($business->name)
                ->process();

            $business->images()
                ->where('group', ImageGroup::LOGO)
                ->where('id', '<>', $image->getKey())
                ->each(function (ImageModel $image) {
                    $image->delete();
                });

            return $image;
        });

        return Response::json([
            'logo_url' => $image->getUrl(Size::MEDIUM),
        ]);
    }

    public function removeLogo(Request $request, BusinessModel $business)
    {
        $business->images()
            ->where('group', ImageGroup::LOGO)
            ->each(function (ImageModel $image) {
                $image->delete();
            });
    }

    /**
     * Update the identifier (username) of a business.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function updateIdentifier(Request $request, BusinessModel $business)
    {
        Gate::inspect('update', $business)->authorize();

        BusinessRepository::updateIdentifier($request, $business);

        return Response::json([
            'identifier' => $business->identifier,
        ]);
    }

    /**
     * Update the information of the business.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function updateInformation(Request $request, BusinessModel $business)
    {
        Gate::inspect('update', $business)->authorize();

        $business = BusinessRepository::update($request, $business);

        $data['id'] = $business->getKey();
        $data['identifier'] = $business->identifier;
        $data['name'] = $business->name;
        $data['display_name'] = $business->display_name;
        $data['email'] = $business->email;
        $data['phone_number'] = $business->phone_number;
        $data['currency_name'] = $business->currency_name;
        $data['statement_description'] = $business->statement_description;
        $data['currency'] = $business->currency;
        $data['introduction'] = $business->introduction;
        $data['country_name'] = $business->country_name;

        return Response::json($data);
    }

    public function createFacebookFeedUrl(BusinessModel $business)
    {
        $random_number = mt_rand(100000, 999999);
        $business->fb_feed_slot = $random_number;
        $feed_url = null;
        try {
            DB::beginTransaction();
            $business->update();
            DB::commit();
            $feed_url = 'http://'.config('app.subdomains.shop', true).'/'.$business->fb_feed_slot.'/products';
            return Response::json(['feed_url' => $feed_url, 'success' => 1]);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            return Response::json(['feed_url' => $feed_url, 'success' => 0]);
        }
    }

    public function updateTaxDetails(Request $request, BusinessModel $business){
        Gate::inspect('update', $business)->authorize();

        $taxSettingsData = $this->validate($request, [
            'individual_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'tax_registration_number' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        try {
            DB::beginTransaction();
            $business->individual_name = $taxSettingsData['individual_name'];
            $business->tax_registration_number = $taxSettingsData['tax_registration_number'];
            $business->save();
            DB::commit();
            Session::flash('success_message', 'Tax Details are successfully updated');
            return redirect()->back();
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            return redirect()->back();
        }
    }

    public function updateSlug(Request $request, BusinessModel $business){
        $data = $this->validate($request, [
            'slug' => [
                'required',
                'regex:/^[0-9A-Za-z\-]+$/',
                'unique:businesses',
                'max:30',
            ],
        ]);

        $slug = $data['slug'];

        $existingBusiness = Business::where('slug', $slug)->first();

        if ($existingBusiness instanceof Business) {
            do{

                $newSlug = $slug . '-' . rand(1,99);
                $existingBusiness = Business::where('slug', $newSlug)->first();

            } while ($existingBusiness instanceof Business);

            $slug = $newSlug;
        }

        $business->slug = $slug;
        $business->save();

        $business->paymentRequests()->where('is_default', 1)->update(['url'=> _env_domain('securecheckout', true) . '/payment-request/@'.$business->slug]);

        Session::flash('success_message', 'Default link is successfully updated.');

        return Response::json($business);
    }
}
