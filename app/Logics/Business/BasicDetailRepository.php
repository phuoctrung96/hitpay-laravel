<?php

namespace App\Logics\Business;

use App\Business;
use Illuminate\Support\Facades\Validator;

class BasicDetailRepository
{
    protected $business;

    public function __construct(Business $business)
    {
        $this->business = $business;
    }

    /**
     * @param $request
     * @return Business $business
     */
    public function updateTaxDetailsFromRequest($request)
    {
        $requestData = Validator::validate($request->all(), [
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

        $this->business->individual_name = $requestData['individual_name'];
        $this->business->tax_registration_number = $requestData['tax_registration_number'];
        $this->business->save();

        return $this->business;
    }
}
