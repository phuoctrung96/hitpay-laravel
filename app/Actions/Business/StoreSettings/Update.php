<?php

namespace App\Actions\Business\StoreSettings;

use App\Actions\Business\BasicDetails\UpdateStripeAccount;
use App\Business;
use App\Events\Business\Updated;
use Carbon\Carbon;
use Illuminate\Support\Facades;
use Illuminate\Support\Facades\Event;

class Update extends Action
{
    /**
     * @return Business\BusinessShopSettings|\Illuminate\Database\Eloquent\Model|null
     * @throws \Exception
     * @throws \Throwable
     */
    public function process(): Business\BusinessShopSettings
    {
        if (! $this->business instanceof Business) {
            throw new \Exception("Business not set!");
        }

        if (isset($this->data['introduction'])) {
            $config = \HTMLPurifier_Config::createDefault();
            $config->set('Cache.SerializerPath', Facades\App::bootstrapPath('cache/serializer_path'));
            $purifier = new \HTMLPurifier($config);
            $this->data['introduction'] = $purifier->purify($this->data['introduction']);
        }

        // updating business column
        $this->business->introduction = $this->data['introduction'];
        $this->business->save();

        UpdateStripeAccount::withBusiness($this->business)->process();

        Event::dispatch(new Updated($this->business));

        // updating shop settings
        $rules = [
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
            'is_redirect_order_completion'=> 'required|boolean',
            'url_redirect_order_completion' => [
                'nullable',
                'string',
                'max:500',
            ],
            'url_facebook' => [
                'nullable',
                'string',
                'max:255',
            ],
            'url_instagram' => [
                'nullable',
                'string',
                'max:255',
            ],
            'url_twitter' => [
                'nullable',
                'string',
                'max:255',
            ],
            'url_tiktok' => [
                'nullable',
                'string',
                'max:255',
            ]
        ];

        $shopSettingsData = Facades\Validator::make($this->data, $rules)->validate();

        if ($shopSettingsData['enable_datetime']) {
            $shopSettingsData['enable_datetime'] = Carbon::createFromFormat('Y-m-d H:i', $shopSettingsData['enable_datetime']);
        }

        $business = $this->business;

        if ($business->shopSettings instanceof Business\BusinessShopSettings) {
            $business->shopSettings->shop_state = $shopSettingsData['shop_state'];
            $business->shopSettings->seller_notes = $shopSettingsData['seller_notes'] ?? null;
            $business->shopSettings->enable_datetime = $shopSettingsData['enable_datetime'] ?? null;
            $business->shopSettings->thank_message = $shopSettingsData['thank_message'] ?? null;
            $business->shopSettings->is_redirect_order_completion = $shopSettingsData['is_redirect_order_completion'];
            $business->shopSettings->url_redirect_order_completion = $shopSettingsData['url_redirect_order_completion'] ?? null;
            $business->shopSettings->url_facebook = $shopSettingsData['url_facebook'] ?? null;
            $business->shopSettings->url_instagram = $shopSettingsData['url_instagram'] ?? null;
            $business->shopSettings->url_twitter = $shopSettingsData['url_twitter'] ?? null;
            $business->shopSettings->url_tiktok = $shopSettingsData['url_tiktok'] ?? null;
            $business->shopSettings->save();
        } else {
            $shopSettings = new Business\BusinessShopSettings();
            $shopSettings->business_id = $this->business->getKey();
            $shopSettings->shop_state = $shopSettingsData['shop_state'];
            $shopSettings->seller_notes = $shopSettingsData['seller_notes'] ?? null;
            $shopSettings->enable_datetime = $shopSettingsData['enable_datetime'] ?? null;
            $shopSettings->thank_message = $shopSettingsData['thank_message'] ?? null;
            $shopSettings->is_redirect_order_completion = $shopSettingsData['is_redirect_order_completion'];
            $shopSettings->url_redirect_order_completion = $shopSettingsData['url_redirect_order_completion'] ?? null;
            $shopSettings->url_facebook = $shopSettingsData['url_facebook'] ?? null;
            $shopSettings->url_instagram = $shopSettingsData['url_instagram'] ?? null;
            $shopSettings->url_twitter = $shopSettingsData['url_twitter'] ?? null;
            $shopSettings->url_tiktok = $shopSettingsData['url_tiktok'] ?? null;
            $shopSettings->save();
        }

        return $this->business->shopSettings()->first();
    }
}
