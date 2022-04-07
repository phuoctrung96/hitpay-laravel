<?php

namespace App\Logics\Business;

use App\Business;
use App\Business\Tax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TaxRepository
{
    /**
     * Create a new tax.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \App\Business\Tax
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public static function store(Request $request, Business $business) : Tax
    {
        $data = Validator::validate($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'applies_locally' => [
                'required',
                'bool',
            ],
            'applies_overseas' => [
                'required',
                'bool',
            ],
            'rate' => [
                'required',
                'numeric',
                'max:100',
                'decimal:0,2',
            ],
        ]);

        // We assume the rate has maximum 2 decimal places.

        $data['rate'] = (float) bcdiv((string ) $data['rate'], '100', 4);

        return DB::transaction(function () use ($business, $data) : Tax {
            $tax = $business->taxes()->create($data);

            return $tax;
        }, 3);
    }

    /**
     * Update an existing tax.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business\Tax $tax
     *
     * @return \App\Business\Tax
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public static function update(Request $request, Tax $tax) : Tax
    {
        // todo validation not correct, either applies locally or applies overseas has to be true.

        $data = Validator::validate($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'applies_locally' => [
                'required',
                'bool',
            ],
            'applies_overseas' => [
                'required',
                'bool',
            ],
            'rate' => [
                'required',
                'numeric',
                'max:100',
                'decimal:0,2',
            ],
        ]);

        // We assume the rate has maximum 2 decimal places.

        $data['rate'] = (float) bcdiv((string ) $data['rate'], '100', 4);

        $tax = DB::transaction(function () use ($tax, $data) : Tax {
            $tax->update($data);

            return $tax;
        }, 3);

        return $tax;
    }

    /**
     * Delete an existing tax.
     *
     * @param \App\Business\Tax $tax
     *
     * @return bool|null
     * @throws \Throwable
     */
    public static function delete(Tax $tax) : ?bool
    {
        return DB::transaction(function () use ($tax) : ?bool {
            return $tax->delete();
        }, 3);
    }
}
