<?php

namespace App\Virtual\Models;

/**
 * @OA\Schema(type="object")
 */
class PaymentRequest
{
    /**
     * @OA\Property(
     *     title="ID",
     *     description="ID",
     *     type="string",
     *     format="uuid",
     *     example=1
     * )
     *
     * @var string
     */
    private $id;    

    /**
     * @OA\Property(
     *     title="Name",
     *     description="Name",
     *     type="string",
     *     example="John Doe"
     * )
     *
     * @var string
     */
    private $name; 

    /**
     * @OA\Property(
     *     title="Email",
     *     description="Email",
     *     type="string",
     *     example="johndoe@test.com"
     * )
     *
     * @var string
     */
    private $email; 

    /**
     * @OA\Property(
     *     title="Phone",
     *     title="Phone",
     *     description="Email",
     *     type="string",
     *     example="+919999999999"
     * )
     *
     * @var string
     */
    private $phone; 

    /**
     * @OA\Property(
     *     title="Purpose",
     *     description="Purpose",
     *     type="string",
     *     example="Payment"
     * )
     *
     * @var string
     */
    private $purpose;

    /**
     * @OA\Property(
     *     title="Currency",
     *     description="Currency",
     *     type="string",
     *     example="sgd"
     * )
     *
     * @var string
     */
    private $currency;

    /**
     * @OA\Property(
     *     title="Status",
     *     description="Status",
     *     type="string",
     *     example="pending"
     * )
     *
     * @var string
     */
    private $status;

    /**
     * @OA\Property(
     *     title="Payment Method",
     *     description="Payment Method",
     *     type="array",
     *     example="paynow_online,card,alipay,wechat",
     *     @OA\Items(type="string")
     * )
     *
     * @var string
     */
    private $payment_methods;

    /**
     * @OA\Property(
     *      title="Amount",
     *      description="Amount of the new payment request",
     *      example=".10",
     *      type="number", 
     *      type="double"
     * )
     *
     * @var double
     */
    public $amount;

    /**
     * @OA\Property(
     *      title="Url",
     *      description="Url of the new payment request",
     *      example="https://securecheckout.hit-pay.com/@HitPay",
     *      type="string"
     * )
     *
     * @var string
     */
    public $url;

    /**
     * @OA\Property(
     *      title="Url",
     *      description="Url of the new payment request",
     *      example="https://securecheckout.hit-pay.com/@HitPay",
     *      type="string"
     * )
     *
     * @var string
     */
    public $webhook;

    /**
     * @OA\Property(
     *      title="Redirect Url",
     *      description="Redirect Url of the new payment request",
     *      example="https://securecheckout.hit-pay.com/@HitPay",
     *      type="string"
     * )
     *
     * @var string
     */
    public $redirect_url;

    /**
     * @OA\Property(
     *      title="Send SMS",
     *      description="Send SMS is used for",
     *      example="true",
     *      type="boolean"
     * )
     *
     * @var boolean
     */
    public $send_sms;

    /**
     * @OA\Property(
     *      title="Send Email",
     *      description="Send Email is used for",
     *      example="true",
     *      type="boolean"
     * )
     *
     * @var boolean
     */
    public $send_email;

    /**
     * @OA\Property(
     *      title="SMS Status",
     *      description="SMS Status",
     *      example="pending",
     *      type="string"
     * )
     *
     * @var boolean
     */
    public $sms_status;

    /**
     * @OA\Property(
     *      title="SMS Status",
     *      description="SMS Status",
     *      example="sent",
     *      type="string"
     * )
     *
     * @var boolean
     */
    public $email_status;

    /**
     * @OA\Property(
     *      title="Allowed Repeated Payments",
     *      description="Allowed Repeated Payments is used for",
     *      example="true",
     *      type="boolean"
     * )
     *
     * @var boolean
     */
    public $allow_repeated_payments;

    /**
     * @OA\Property(
     *     title="Expiry Date",
     *     description="Expiry Date",
     *     example="2020-01-27 17:50:45",
     *     format="date",
     *     type="string"
     * )
     *
     * @var \DateTime
     */
    private $expiry_date;

    /**
     * @OA\Property(
     *     title="Created at",
     *     description="Created at",
     *     example="2020-01-27 17:50:45",
     *     format="datetime",
     *     type="string"
     * )
     *
     * @var \DateTime
     */
    private $created_at;

    /**
     * @OAS\Property(
     *     title="Updated at",
     *     description="Updated at",
     *     example="2020-01-27 17:50:45",
     *     format="datetime",
     *     type="string"
     * )
     *
     * @var \DateTime
     */
    private $updated_at;

    /**
     * @OA\Property(
     *     title="Deleted at",
     *     description="Deleted at",
     *     example="2020-01-27 17:50:45",
     *     format="datetime",
     *     type="string"
     * )
     *
     * @var \DateTime
     */
    private $deleted_at;

    /**
     * @OA\Property(
     *      title="Business ID",
     *      description="Business id of the new payment request",
     *      format="uuid",
     *      type="string",
     *      example=1
     * )
     *
     * @var string
     */
    public $business_id;
}