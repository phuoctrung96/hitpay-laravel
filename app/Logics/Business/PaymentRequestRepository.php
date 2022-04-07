<?php

namespace App\Logics\Business;

use App\Business\PaymentRequest;
use Illuminate\Support\Facades\DB;

class PaymentRequestRepository
{
    /**
     * Store a new payment request.
     *
     * @param array $data
     *
     * @return \App\PaymentRequest
     */
    public static function store(array $data) : PaymentRequest
    {
        $paymentRequest = DB::transaction(function () use ($data) : PaymentRequest {
            $paymentRequest = PaymentRequest::create($data);

            return $paymentRequest;
        }, 3);

        return $paymentRequest;
    }

    /**
     * Update a payment request.
     *
     * @param \App\PaymentRequest $paymentRequest
     * @param array $data
     *
     * @return \App\PaymentRequest
     */
    public static function update(PaymentRequest $paymentRequest, array $data) : PaymentRequest
    {
        $paymentRequest = DB::transaction(function () use ($paymentRequest, $data) : PaymentRequest {
            $paymentRequest->update($data);

            return $paymentRequest;
        }, 3);

        return $paymentRequest;
    }

    /**
     * Delete a paymentRequest.
     *
     * @param \App\PaymentRequest $paymentRequest
     *
     * @return ?bool
     */
    public static function delete(PaymentRequest $paymentRequest) : ?bool
    {
        return DB::transaction(function () use ($paymentRequest) : ?bool {
            return $paymentRequest->delete();
        }, 3);
    }
}
