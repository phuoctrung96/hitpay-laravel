<?php

namespace App\Http\Controllers\Shop;

use App\Actions\Business\Stripe\Charge\Source;
use App\Business;
use App\Business\Charge;
use App\Business\Order;
use App\Business\OrderedProduct;
use App\Business\ProductVariation;
use App\Business\Shipping;
use App\Business\ShippingCountry;
use App\Services\ShopCheckout;
use App\Enumerations\AllCountryCode;
use App\Enumerations\Business\Channel;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\Business\OrderStatus;
use App\Http\Resources\Business\Charge as ChargeResource;
use App\Http\Resources\Business\PaymentIntent as PaymentIntentResource;
use Exception;
use HitPay\Stripe\Charge as StripeCharge;
use HitPay\Stripe\Core;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class CheckoutController extends Controller
{
    public function showPreCheckoutPage(Request $request, Business $business)
    {
        $cart = $request->session()->get('cart-' . $business->getKey(), [
            'checksum' => '',
            'products' => [
                //
            ],
        ]);


        if (empty($cart['products'])) {
            App::abort(404);
        }

        if ($business->enabled_shipping && !$business->can_pick_up && $business->shippings_count < 1) {
            App::abort(404);
        }

        $variations = $business->productVariations()->with([
            'product' => function (BelongsTo $query) {
                $query->with('images');
            },
        ])->findMany(Collection::make($cart['products'])->pluck('variation_id'));

        $totalCartAmount = 0;
        $totalCartQuantity = 0;
        $variationsArray = [];

        foreach ($cart['products'] as $key => $value) {
            $variation = $variations->find($value['variation_id']);

            if (!$variation instanceof ProductVariation) {
                continue;
            }

            $totalCartAmount = $totalCartAmount + bcmul($value['quantity'], $variation->price);
            $totalCartQuantity = $totalCartQuantity + $value['quantity'];

            $variationsArray[$key] = [
                'cart' => $value,
                'model' => $variation->toArray(),
                'image' => optional($variation->product->images->first())->getUrl(),
            ];
        }

        $discounts = $business->discounts->sortByDesc('minimum_cart_amount');

        $discount = $this->getDiscount($discounts, $totalCartAmount);

        $business->slots = json_decode($business->slots);

        $shipping_discount = $business->shipping_discount()->first();

        $checkoutOptions = $this->getCheckoutOptions($business);

        return Response::view('shop.pre-checkout', [
            'business' => $business,
            'checkoutOptions' => $checkoutOptions,
            'totalCartAmount' => $totalCartAmount,
            'totalCartQuantity' => $totalCartQuantity,
            'discount' => $discount,
            'shipping_discount' => $shipping_discount,
            'variations' => $variationsArray,
        ]);
    }

    public function doCheckout(Request $request, Business $business, ShopCheckout $shopCheckout)
    {
        $cart = $request->session()->pull('cart-' . $business->getKey(), [
            'checksum' => '',
            'products' => [
                //
            ],
        ]);

        if (empty($cart['products'])) {
            App::abort(404);
        }

        if ($business->enabled_shipping && !$business->can_pick_up && $business->shippings_count < 1) {
            App::abort(404);
        }

        $rules = [
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255',
            'phone_number' => 'required|string',
            'remark' => 'nullable|string|max:65536',
            'discount.name' => 'nullable|string',
            'discount.amount' => 'nullable|numeric',
            'coupon_amount' => 'nullable|numeric',
        ];

        if ($business->enabled_shipping){
            $rules += [
                'shipping_rate' => 'required|numeric',
                'date_slot' => [
                    'nullable',
                    'array',
                ],
                'date_slot.date' => [
                    'date_format:"Y-m-d"',
                ],
                'date_slot.times' => [
                    'array',
                ],
                'date_slot.times.from' => [
                    'string',
                ],
                'date_slot.times.to' => [
                    'string',
                ],
            ];
        }

        if ($business->enabled_shipping && $business->can_pick_up) {
            $rules['customer_pickup'] = [
                'required_without:shipping',
                'bool',
            ];
        }

        if ($business->enabled_shipping && !$request->get('customer_pickup')) {
            $shippings = $business->shippings;

            $shippings->load('countries');

            $shippingCountries = [];

            $isGlobal = false;

            $shippings->each(function (Shipping $shipping) use (&$shippingCountries, &$isGlobal) {
                if ($shipping->countries->count() === 0) {
                    $shippingCountries = array_values(AllCountryCode::listConstants());

                    $isGlobal = true;
                } else {
                    $shipping->countries->each(function (ShippingCountry $country) use (
                        &$shippingCountries, &$isGlobal
                    ) {
                        if (!$isGlobal) {
                            $shippingCountries[] = $country->country;
                        }
                    });
                }
            });

            $shippingCountries = array_unique($shippingCountries);

            $rules += [
                'shipping.address' => [
                    'required',
                    'array',
                ],
                'shipping.address.street' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'shipping.address.city' => [
                    'nullable',
                    'string',
                    'max:64',
                ],
                'shipping.address.postal_code' => [
                    'required',
                    'string',
                    'max:8',
                ],
                'shipping.address.state' => [
                    'nullable',
                    'string',
                    'max:64',
                ],
                'shipping.address.country' => [
                    'required',
                    'string',
                    Rule::in($shippingCountries),
                ],
                'shipping.option' => [
                    'required',
                    Rule::in($shippings->pluck('id')),
                ],
            ];
        }

        $data = $this->validate($request, $rules, [], [
            'shipping.address.street' => 'shipping address street',
            'shipping.address.city' => 'shipping address city',
            'shipping.address.postal_code' => 'shipping address postal code',
            'shipping.address.state' => 'shipping address state',
            'shipping.address.country' => 'shipping address country',
            'shipping.option' => 'shipping option',
        ]);

        if ($business->enabled_shipping) {
            if (!($data['customer_pickup'] ?? false)) {
                // If not customer pickup, then we have to get the shipping method.
                $shipping = $business->shippings->find($data['shipping']['option']);

                if (!$shipping instanceof Shipping) {
                    throw ValidationException::withMessages([
                        'shipping.option' => 'The selected shipping option is invalid.',
                    ]);
                }
            }
        }

        $order = new Order;

        $order->customer_email = $data['email'];
        $order->customer_phone_number = $data['phone_number'] ?? null;
        if ($business->enabled_shipping) {
            $order->customer_street = $data['shipping']['address']['street'] ?? null;
            $order->customer_city = $data['shipping']['address']['city'] ?? null;
            $order->customer_state = $data['shipping']['address']['state'] ?? null;
            $order->customer_postal_code = $data['shipping']['address']['postal_code'] ?? null;
            $order->customer_country = $data['shipping']['address']['country'] ?? null;
            $order->customer_pickup = $data['customer_pickup'] ?? false;
        }
        $order->customer_name = $data['first_name'] . ' ' . $data['last_name'] ;
        $order->channel = Channel::STORE_CHECKOUT;
        $order->currency = $business->currency;
        $order->remark = $data['remark'] ?? null;
        $order->status = OrderStatus::DRAFT;
        $order->automatic_discount_name = $data['discount']['name'];
        $order->automatic_discount_amount = $data['discount']['amount'];
        $order->coupon_amount = $data['coupon_amount'];

        $productsCollection = Collection::make($cart['products']);

        $products = $business->productVariations()->with('product')
            ->whereIn('id', $productsCollection->pluck('variation_id'))->get();

        $totalQuantity = 0;
        $productsArray = [];

        foreach ($productsCollection as $value) {
            /** @var \App\Business\ProductVariation $variation */
            $variation = $products->where('id', $value['variation_id'])->first();

            $orderedProduct = new OrderedProduct;

            $orderedProduct->business_product_id = $variation->getKey();
            $orderedProduct->name = $variation->product->name;
            $orderedProduct->description = $variation->description;
            $orderedProduct->variation_key_1 = $variation->product->variation_key_1;
            $orderedProduct->variation_value_1 = $variation->variation_value_1;
            $orderedProduct->variation_key_2 = $variation->product->variation_key_2;
            $orderedProduct->variation_value_2 = $variation->variation_value_3;
            $orderedProduct->variation_key_3 = $variation->product->variation_key_3;
            $orderedProduct->variation_value_3 = $variation->variation_value_3;
            $orderedProduct->quantity = $value['quantity'];
            $orderedProduct->remark = $value['remark'] ?? null;
            $orderedProduct->unit_price = $variation->price;
            $orderedProduct->discount_amount = 0;
            $orderedProduct->price = bcmul($orderedProduct->quantity, $orderedProduct->unit_price)
                - $orderedProduct->discount_amount;

            if ($productTax = $variation->product->tax) {
                $orderedProduct->tax_name = $productTax->name;
                $orderedProduct->tax_rate = (float)$productTax->rate;
            } else {
                $orderedProduct->tax_rate = 0;
            }

            $orderedProduct->tax_amount = $orderedProduct->price
                - (int)bcdiv($orderedProduct->price, (1 + $orderedProduct->tax_rate));

            if ($image = $variation->product->images()->first()) {
                $orderedProduct->business_image_id = $image->getKey();
            }

            $totalQuantity += $orderedProduct->quantity;

            $productsArray[] = $orderedProduct;
        }
        if ($business->enabled_shipping) {
            if ($data['date_slot']) {
                $order->slot_date = $data['date_slot']['date'] ? Date::parse($data['date_slot']['date']) : null;
                $order->slot_time = $data['date_slot']['times'] ? json_encode($data['date_slot']['times']) : null;
            }
        }

        if ($business->enabled_shipping) {
            if (!$order->customer_pickup) {
                $order->business_shipping_id = $shipping->getKey();
                $order->shipping_method = $shipping->name;

                $order->shipping_amount = $data['shipping_rate'];

                if ($shippingTax = $shipping->tax) {
                    $order->shipping_tax_name = $shippingTax->name;
                    $order->shipping_tax_rate = (float)$shippingTax->rate;
                    $order->shipping_tax_amount = $order->shipping_amount
                        - (int)bcdiv($order->shipping_amount, (1 + $order->shipping_tax_rate));
                } else {
                    $order->shipping_tax_rate = 0;
                    $order->shipping_tax_amount = 0;
                }
            }
        }

        return DB::transaction(function () use (
            $business, $order, $productsArray, $request, $shopCheckout
        ) {
            $business->orders()->save($order);

            if ($business->customers()->where('email', $order->customer_email)->first() === null) {
                try {
                    $business->customers()->create([
                        'name' => $order->customer_name,
                        'email' => $order->customer_email,
                        'phone_number' => $order->customer_phone_number,
                        'street' => $order->customer_street,
                        'city' => $order->customer_city,
                        'state' => $order->customer_state,
                        'postal_code' => $order->customer_postal_code,
                        'country' => $order->customer_country,
                    ]);
                } catch (Exception $exception) {
                    //
                }
            }

            $order->products()->saveMany($productsArray);
            $order->checkout();

            $charge = new Charge;

            $charge->channel = $order->channel;

            $charge->business_customer_id = $order->business_customer_id;
            $charge->customer_name = $order->customer_name;
            $charge->customer_email = $order->customer_email;
            $charge->customer_phone_number = $order->customer_phone_number;
            $charge->customer_street = $order->customer_street;
            $charge->customer_city = $order->customer_city;
            $charge->customer_state = $order->customer_state;
            $charge->customer_postal_code = $order->customer_postal_code;
            $charge->customer_country = $order->customer_country;
            $charge->currency = $order->currency;
            $charge->remark = Str::limit($order->products->pluck('name')->implode(', '));
            $charge->amount = $order->amount;
            $charge->status = ChargeStatus::REQUIRES_PAYMENT_METHOD;

            $charge->target()->associate($order);

            $business->charges()->save($charge);

            $paymentRequest = $shopCheckout->createPaymentRequest($order, $business);

            return $paymentRequest['url'];

        });
    }

    // protected

    /**
     * Create payment intent for specific charge.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business\Charge $charge
     *
     * @return \App\Http\Resources\Business\PaymentIntent
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Throwable
     */
    public function createPaymentIntentForCharge(Request $request, Business $business, Charge $charge)
    {
        /**
         * @var \App\Business\PaymentProvider $provider
         */
        $provider = $business->paymentProviders->where('payment_provider', $business->payment_provider)->first();

        if (!$provider) {
            throw new Exception('Business is not properly setup.');
        }

        $data = $this->validate($request, [
            'method' => [
                'required',
                Rule::in([
                    'card',
                    'alipay',
                    'wechat',
                ]),
            ],
        ]);

        if ($data['method'] === 'card') {
            $stripePaymentIntent = StripeCharge::new($business->payment_provider)
                ->createPaymentIntent($provider->payment_provider_account_id, $charge->currency, $charge->amount,
                    $business->statementDescription(), [
                        'remark' => $charge->remark,
                        'payment_method_types' => [
                            $data['method'],
                        ],
                        'capture_method' => 'automatic',
                    ]);

            try {
                $paymentIntent = DB::transaction(function () use ($business, $provider, $charge, $stripePaymentIntent) {
                    $business->charges()->save($charge);

                    $metadata = $stripePaymentIntent->metadata->toArray();

                    $metadata['charge_id'] = $charge->getKey();

                    $stripePaymentIntent = StripeCharge::new($business->payment_provider)
                        ->updatePaymentIntent($stripePaymentIntent->id, $metadata);

                    return $charge->paymentIntents()->create([
                        'business_id' => $charge->business_id,
                        'payment_provider' => $provider->payment_provider,
                        'payment_provider_account_id' => $provider->payment_provider_account_id,
                        'payment_provider_object_type' => $stripePaymentIntent->object,
                        'payment_provider_object_id' => $stripePaymentIntent->id,
                        'payment_provider_method' => $stripePaymentIntent->type,
                        'currency' => $stripePaymentIntent->currency,
                        'amount' => $stripePaymentIntent->amount,
                        'status' => $stripePaymentIntent->status,
                        'data' => $stripePaymentIntent->toArray(),
                    ]);
                });
            } catch (Exception $exception) {
                $stripePaymentIntent->cancel();

                throw $exception;
            }

            return new PaymentIntentResource($paymentIntent);
        }

        $data = [
            'method' => $data['method'],
        ];

        if ($data['method'] === 'alipay') {
            $data['return_url'] = URL::route('shop.charge.alipay', [
                'business_id' => $business->getKey(),
                'charge_id' => $charge->getKey(),
            ]);
        }

        $paymentIntent = Source\Create::withBusiness($business)->businessCharge($charge)->data($data)->process();

        return new PaymentIntentResource($paymentIntent);
    }

    /**
     * @param \App\Business $business
     * @param \App\Business\Charge $charge
     *
     * @return \App\Http\Resources\Business\Charge
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function getCharge(Business $business, Charge $charge)
    {
        // how to prevent charge exposed? by right anyone with this url can see the things.
        return new ChargeResource($charge);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business\Charge $charge
     */
    public function showAlipayCallback(Request $request, Business $business, Charge $charge)
    {
        // display if alipay success or failed.
    }

    public function getJsonCoupon(Request $request, Business $business){
        $coupon = $business->coupons()->where('code', $request->coupon_code)->first();
        return Response::json($coupon ? $coupon->toArray() : null);
    }
}
