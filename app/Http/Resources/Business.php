<?php

namespace App\Http\Resources;

use App\Business\Image as ImageModel;
use App\Enumerations\Image\Size;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Enumerations\PaymentProvider;
use Illuminate\Support\Facades\URL;

/**
 * @mixin \App\Business
 */
class Business extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        $data['xero_organization_name'] = optional($this->xeroOrganizations->first())->name;
        $data['id'] = $this->getKey();
        $data['identifier'] = $this->identifier;
        $data['store_url'] = $this->identifier
            ? URL::route('shop.business', $this->identifier)
            : URL::route('shop.business', $this->getKey());

        if ($this->relationLoaded('owner') && $this->owner) {
            $data['owner'] = new User($this->owner);
        }

        $data['stripe_enabled'] = $this->paymentProviders->where('payment_provider', $this->payment_provider)->count() > 0;
        $data['paynow_enabled'] = $this->paymentProviders->where('payment_provider', PaymentProvider::DBS_SINGAPORE)->count() > 0;
        $data['payment_request_default_link'] = optional($this->paymentRequests()->where('is_default', true)->first())->url;
        $data['name'] = $this->name;
        $data['display_name'] = $this->display_name;
        $data['email'] = $this->email;
        $data['phone_number'] = $this->phone_number;
        $data['address'] = [
            'street' => $this->street,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'country_name' => $this->country_name
        ];
        $data['address_line'] = $this->getAddress();
        $data['country'] = $this->country;
        $data['can_pick_up'] = $this->can_pick_up;
        $data['slots'] = $this->slots;
        $data['thank_message'] = $this->thank_message;
        $data['enabled_shipping'] = $this->enabled_shipping;
        $data['introduction'] = $this->introduction;
        $data['seller_notes'] = $this->seller_notes;
        $data['statement_description'] = $this->statement_description;
        $data['founding_date'] = optional($this->founding_date)->toDateString();
        $data['locale'] = $this->locale;
        $data['currency'] = $this->currency;
        $data['currency_name'] = $this->currency_name;
        $data['created_at'] = $this->created_at->toAtomString();
        $data['updated_at'] = $this->updated_at->toAtomString();
        $data['is_verified'] = $this->is_verified;
        $data['is_email_verified'] = $this->is_email_verified;
        $data['is_phone_number_verified'] = $this->is_phone_number_verified;
        $data['is_banned'] = $this->isBanned();
        $data['is_deactivated'] = $this->isDeactivated();

        $data = array_merge($data, $this->getLogoResource());

        return $data;
    }

    private function getLogoResource()
    {
        $logo = $this->logo()->first();

        $default_logo_url = '/hitpay/images/product.jpg';

        if ($logo instanceof ImageModel) {
            $logo_url = $logo->getUrl(Size::MEDIUM);
        }

        $data['default_logo_url'] = $default_logo_url;

        $data['logo_url'] = $logo_url ?? null;

        return $data;
    }
}
