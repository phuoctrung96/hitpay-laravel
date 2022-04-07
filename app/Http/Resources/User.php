<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\User
 */
class User extends JsonResource
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
        $data['id'] = $this->getKey();
        $data['display_name'] = $this->display_name;
        $data['first_name'] = $this->first_name;
        $data['last_name'] = $this->last_name;
        $data['birth_date'] = optional($this->birth_date)->toDateString();
        $data['gender'] = $this->gender;
        $data['referral_code'] = $this->referral_code;
        $data['email'] = $this->email;
        $data['phone_number'] = $this->phone_number;
        $data['locale'] = $this->locale;

        if ($this->relationLoaded('role') && $this->role) {
            $data['role'] = new Role($this->role);
        }

        if ($this->relationLoaded('businessesOwned')) {
            $data['businesses']['owned'] = Business::collection($this->businessesOwned)->toArray($request);
        }

        if ($this->relationLoaded('businesses')) {
            $data['businesses']['owned'] = Business::collection($this->businesses)->toArray($request);
        }

        if ($this->businessesManagedLoaded()) {
            $data['businesses']['managed'] = Business::collection($this->businessesManaged())->toArray($request);
        }

        $data['created_at'] = $this->created_at->toAtomString();
        $data['updated_at'] = $this->updated_at->toAtomString();
        $data['password_updated_at'] = $this->password_updated_at
            ? $this->password_updated_at->toAtomString()
            : $this->password_updated_at;
        $data['email_login_enabled'] = $this->email_login_enabled;
        $data['is_verified'] = $this->is_verified;
        $data['is_email_verified'] = $this->is_email_verified;
        $data['is_phone_number_verified'] = $this->is_phone_number_verified;
        $data['is_authentication_secret_enabled'] = $this->isAuthenticationSecretEnabled();
        $data['is_banned'] = $this->isBanned();
        $data['is_deactivated'] = $this->isDeactivated();

        return $data;
    }
}
