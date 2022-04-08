<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Business;
use App\Business as BusinessModel;
use App\Business\Image as ImageModel;
use App\Enumerations\Image\Size;
use App\Http\Controllers\Controller;
use App\Logics\BusinessRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use HitPay\Image\Processor as ImageProcessor;
use App\Enumerations\Business\ImageGroup;

class StoreSettingsController extends Controller
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

        $enableDate = $business->enable_datetime ? Carbon::parse($business->enable_datetime)->format('Y-m-d') : null;
        $enableTime = $business->enable_datetime ? Carbon::parse($business->enable_datetime)->format('h:i A') : null;

        $coverImage = $business->coverImage()->first();

        $default_cover_image_url = '/hitpay/images/product.jpg';
        if ($coverImage instanceof ImageModel) {
            $cover_image_url = $coverImage->getUrl(Size::MEDIUM);
        }
        $data = [
            'default_cover_image_url' => $default_cover_image_url,
            'cover_image_url' => $cover_image_url ?? null
        ];

        return Response::view('dashboard.business.store-settings.index', compact('business', 'enableDate', 'enableTime', 'data'));
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

        $shopSettingsData = $this->validate($request, [
            'can_pick_up' => [
                'required',
                'bool',
            ],
            'slots' => [
                'nullable',
            ],
            'seller_notes' => [
                'nullable',
                'string',
                'max:65536',
            ],
            'shop_state' => 'required|boolean',
            'enable_datetime' => 'nullable|string',
            'thank_message' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'enabled_shipping' => 'required|boolean',
            'is_redirect_order_completion'=> 'required|boolean',
            'url_redirect_order_completion' => [
                'nullable',
                'string',
                'max:500',
            ],
        ]);


        if ($shopSettingsData['enable_datetime'])
            $shopSettingsData['enable_datetime'] = Carbon::createFromFormat('Y-m-d H:i', $shopSettingsData['enable_datetime']);
        else $shopSettingsData['enable_datetime'] = $shopSettingsData['enable_datetime'];

        DB::transaction(function () use ($business, $shopSettingsData) {
            if ($business->shopSettings) {
                $business->shopSettings->shop_state = $shopSettingsData['shop_state'];
                $business->shopSettings->can_pick_up = $shopSettingsData['can_pick_up'];
                $business->shopSettings->slots = $shopSettingsData['slots'];
                $business->shopSettings->seller_notes = $shopSettingsData['seller_notes'];
                $business->shopSettings->enable_datetime = $shopSettingsData['enable_datetime'];
                $business->shopSettings->thank_message = $shopSettingsData['thank_message'];
                $business->shopSettings->enabled_shipping = $shopSettingsData['enabled_shipping'];
                $business->shopSettings->is_redirect_order_completion = $shopSettingsData['is_redirect_order_completion'];
                $business->shopSettings->url_redirect_order_completion = $shopSettingsData['url_redirect_order_completion'];
                $business->shopSettings->save();

            } else $business->shopSettings()->create($shopSettingsData);
        });

        $business->load('shopSettings');

        return Response::json($business);
    }

    /**
     * Save date slots for pickups.
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
    public function saveSlots(Request $request, BusinessModel $business){
        Gate::inspect('update', $business)->authorize();

        $shopSettingsData = $this->validate($request, [
            'can_pick_up' => [
                'required',
                'bool',
            ],
            'slots' => [
                'nullable',
            ],
        ]);

        if ($business->shopSettings) {
            $business->shopSettings()->update($shopSettingsData);
        } else $business->shopSettings()->create($shopSettingsData);

        $data['id'] = $business->getKey();
        $data['identifier'] = $business->identifier;
        $data['name'] = $business->name;
        $data['display_name'] = $business->display_name;
        $data['email'] = $business->email;
        $data['phone_number'] = $business->phone_number;
        $data['street'] = $business->street;
        $data['city'] = $business->city;
        $data['state'] = $business->state;
        $data['postal_code'] = $business->postal_code;
        $data['country'] = $business->country;
        $data['country_name'] = $business->country_name;
        $data['currency_name'] = $business->currency_name;
        $data['statement_description'] = $business->statement_description;
        $data['currency'] = $business->currency;
        $data['can_pick_up'] = $business->can_pick_up;
        $data['slots'] = $business->slots;
        $data['seller_notes'] = $business->seller_notes;
        $data['shop_state'] = $business->shop_state;
        $data['introduction'] = $business->introduction;
        $data['seller_notes'] = $business->seller_notes;
        $data['is_redirect_order_completion'] = $business->is_redirect_order_completion;
        $data['url_redirect_order_completion'] = $business->url_redirect_order_completion;

        return Response::json($data);
    }
    /**
     * Upload cover image
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function uploadCoverImage(Request $request, BusinessModel $business)
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
            $image = ImageProcessor::new($business, ImageGroup::COVER, $image)
                ->setCaption($business->name)
                ->process();

            $business->images()
                ->where('group', ImageGroup::COVER)
                ->where('id', '<>', $image->getKey())
                ->each(function (ImageModel $image) {
                    $image->delete();
                });

            return $image;
        });

        return Response::json([
            'cover_image_url' => $image->getUrl(Size::MEDIUM),
        ]);
    }

    /**
     * Remove cover image
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function removeCoverImage(Request $request, BusinessModel $business)
    {
        $business->images()
            ->where('group', ImageGroup::COVER)
            ->each(function (ImageModel $image) {
                $image->delete();
            });
    }
}
