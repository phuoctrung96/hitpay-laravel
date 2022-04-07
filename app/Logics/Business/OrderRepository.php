<?php

namespace App\Logics\Business;

use App\Business;
use App\Business\Customer;
use App\Business\Order;
use App\Business\OrderedProduct;
use App\Enumerations\Business\Channel;
use App\Enumerations\Business\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderRepository
{
    /**
     * Create a new order.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \App\Business\Order
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public static function store(Request $request, Business $business, string $channel = null, string $status = null
    ) : Order {
        $data = Validator::validate($request->all(), [
            'customer_id' => [
                'nullable',
                Rule::exists('business_customers', 'id')->where('business_id', $business->getKey()),
            ],
            'customer_pickup' => [
                'nullable',
                'bool',
            ],
            'discount_reason' => [
                'required_with:discount',
                'string',
                'max:255',
            ],
            'discount_amount' => [
                'nullable',
                'decimal:0,2',
                'min:0',
                'max:100',
            ],
            'remark' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $order = new Order;

        if (!empty($data['customer_id'])) {
            $order->setCustomer(
                Customer::where('business_id', $business->id)->findOrFail($data['customer_id']),
                true
            );
        }

        $order->channel = $channel ?? Channel::DEFAULT;
        $order->currency = $business->currency;
        $order->remark = $data['remark'] ?? null;
        $order->status = $status ?? OrderStatus::DRAFT;
        $order->customer_pickup = $data['customer_pickup'] ?? false;
        $order->automatic_discount_reason = $data['discount_reason'] ?? null;
        $order->additional_discount_amount = isset($data['discount_amount']) ? getRealAmountForCurrency($order->currency, $data['discount_amount']): 0;

        return DB::transaction(function () use ($business, $order) : Order {
            $business->orders()->save($order);

            $order = $order->refresh();

            return $order;
        }, 3);
    }

    /**
     * Update an existing order.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\Order $order
     *
     * @return \App\Business\Order
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public static function update(Request $request, Business $business, Order $order) : Order
    {
        $data = Validator::validate($request->all(), [
            'customer_id' => [
                'nullable',
                Rule::exists('business_customers', 'id')->where('business_id', $business->getKey()),
            ],
            'discount_reason' => [
                'required_with:discount',
                'string',
                'max:255',
            ],
            'discount_amount' => [
                'nullable',
                'decimal:0,2',
                'min:0',
                'max:100',
            ],
            'remark' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        if (!empty($data['customer_id'])) {
            $order->setCustomer(
                Customer::where('business_id', $business->getKey())->findOrFail($data['customer_id']),
                $order->status === OrderStatus::DRAFT
            );
        }

        $order->remark = $data['remark'] ?? null;
        $order->automatic_discount_reason = $data['discount_reason'] ?? null;
        $order->additional_discount_amount = isset($data['discount_amount']) ? getRealAmountForCurrency($order->currency, $data['discount_amount']): 0;

        return DB::transaction(function () use ($order) : Order {
            $order->save();

            return $order;
        }, 3);
    }

    /**
     * Delete a draft order.
     *
     * @param \App\Business $business
     * @param \App\Business\Order $order
     *
     * @return \App\Business\Order
     * @throws \Throwable
     */
    public static function delete(Business $business, Order $order) : Order
    {
        if ($order->isNotEditable()) {
            App::abort(403, 'You can\'t delete a non-draft status order.');
        }

        return DB::transaction(function () use ($order) : ?bool {
            return $order->delete();
        }, 3);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\Order $order
     *
     * @return \App\Business\Order
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public static function addProduct(Request $request, Business $business, Order $order, $isCheckEdit = true) : Order
    {
        if ($isCheckEdit && $order->isNotEditable()) {
            App::abort(403, 'You can\'t add product to a non-draft status order.');
        }

        $data = Validator::validate($request->all(), [
            'id' => [
                'required',
                'uuid',
            ],
            'quantity' => [
                'required',
                'int',
                'min:1',
            ],
            'remark' => [
                'nullable',
                'string',
                'max:255',
            ],
            'skip_quantity_check' => [
                'sometimes',
                'required',
                'bool',
            ],
        ]);

        $variation = $business->productVariations()->whereHas('product')->find($data['id']);

        if (!$variation) {
            throw ValidationException::withMessages([
                'id' => Lang::get('validation.exists', [
                    'attribute' => 'ID',
                ]), // todo
            ]);
        }

        $exists = $order->products()->where('business_product_id', $variation->id)->first();

        if ($exists) {
            $data['quantity'] = $exists->quantity + $data['quantity'];
        }

        if (($data['skip_quantity_check'] ?? false) === false && is_int($variation->quantity)
            && $variation->quantity < $data['quantity']) {
            throw ValidationException::withMessages([
                'quantity' => 'Insufficient stock level', // todo
            ]);
        }

        $product = $variation->product()->first();

        $productData['business_product_id'] = $variation->getKey();
        $productData['name'] = $product->name;
        $productData['description'] = $variation->description;
        $productData['variation_key_1'] = $product->variation_key_1;
        $productData['variation_value_1'] = $variation->variation_value_1;
        $productData['variation_key_2'] = $product->variation_key_2;
        $productData['variation_value_2'] = $variation->variation_value_3;
        $productData['variation_key_3'] = $product->variation_key_3;
        $productData['variation_value_3'] = $variation->variation_value_3;
        $productData['quantity'] = $data['quantity'];
        $productData['unit_price'] = $variation->price;
        $productData['discount_amount'] = 0;
        $productData['price'] = ($productData['quantity'] * $productData['unit_price'])
            - $productData['discount_amount'];

        if ($tax = $product->tax()->first()) {
            $productData['tax_name'] = $tax->name;
            $productData['tax_rate'] = (float) $tax->rate;
        } else {
            $productData['tax_rate'] = 0;
        }

        $productData['tax_amount'] = $productData['price']
            - (int) bcdiv($productData['price'], (1 + $productData['tax_rate']));

        $productData['remark'] = $data['remark'] ?? null;

        if ($image = $product->images()->first()) {
            $productData['business_image_id'] = $image->getKey();
        }

        DB::transaction(function () use ($order, $exists, $productData) : void {
            if ($exists) {
                $exists->update($productData);
            } else {
                $order->products()->create($productData);
            }
        });

        return $order;
    }

    /**
     * Update an existing product.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\Order $order
     * @param \App\Business\OrderedProduct $orderedProduct
     *
     * @return \App\Business\Order
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public static function updateProduct(
        Request $request, Business $business, Order $order, OrderedProduct $orderedProduct
    ) : Order {
        $data = Validator::validate($request->all(), [
            'quantity' => [
                'required',
                'int',
                'min:0',
            ],
            'remark' => [
                'nullable',
                'string',
                'max:255',
            ],
            'skip_quantity_check' => [
                'sometimes',
                'required',
                'bool',
            ],
        ]);

        if ($data['quantity'] === 0) {
            return static::removeProduct($business, $order, $orderedProduct);
        }

        $variation = $business->productVariations()->whereHas('product')->find($orderedProduct->business_product_id);

        if (!$variation) {
            static::removeProduct($business, $order, $orderedProduct);

            App::abort(403, 'This product is not longer available.');
        }

        if (($data['skip_quantity_check'] ?? false) === false && is_int($variation->quantity)
            && $variation->quantity < $data['quantity']) {
            throw ValidationException::withMessages([
                'quantity' => 'Insufficient stock level', // todo
            ]);
        }
        $orderedProduct->quantity = $data['quantity'];
        $orderedProduct->price = ($orderedProduct->quantity * $orderedProduct->unit_price)
            - $orderedProduct->discount_amount;

        // todo discount_amount validation should be here.

        $orderedProduct->tax_amount = $orderedProduct->price
            - (int) bcdiv($orderedProduct->price, (1 + $orderedProduct->tax_rate));

        $orderedProduct->remark = $data['remark'] ?? null;

        DB::transaction(function () use ($orderedProduct) : void {
            $orderedProduct->save();
        }, 3);

        return $order;
    }

    /**
     * Remove an existing product from order.
     *
     * @param \App\Business $business
     * @param \App\Business\Order $order
     * @param \App\Business\OrderedProduct $product
     *
     * @return \App\Business\Order
     * @throws \Throwable
     */
    public static function removeProduct(Business $business, Order $order, OrderedProduct $product) : Order
    {
        if ($order->isNotEditable()) {
            App::abort(403, 'You can\'t remove a product from a non-draft status order.');
        }

        DB::transaction(function () use ($product) : void {
            $product->delete();
        }, 3);

        return $order;
    }
}
