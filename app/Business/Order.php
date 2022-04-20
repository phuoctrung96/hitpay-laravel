<?php

namespace App\Business;

use App\Business;
use App\Enumerations\Business\Channel;
use App\Enumerations\Business\OrderStatus;
use App\Notifications\NotifyNewCheckoutOrder;
use App\Notifications\NotifyOrderChanges;
use App\Notifications\NotifyOrderConfirmation;
use App\Notifications\RemindLowQuantity;
use HitPay\Agent\LogHelpers;
use HitPay\Business\Contracts\Chargeable as ChargeableContract;
use HitPay\Business\Contracts\HasCustomer as HasCustomerContract;
use HitPay\Business\Contracts\Ownable as BusinessOwnableContract;
use HitPay\Business\HasCustomer;
use HitPay\Business\Ownable as BusinessOwnable;
use HitPay\Model\UsesUuid;
use HitPay\Shopify\Shopify;
use HitPay\User\Contracts\Ownable as UserOwnableContract;
use HitPay\User\Ownable as UserOwnable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class Order extends Model
    implements BusinessOwnableContract, ChargeableContract, HasCustomerContract, UserOwnableContract
{
    use BusinessOwnable, HasCustomer, LogHelpers, Notifiable, UserOwnable, UsesUuid;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'business_orders';

    /**
     * The number of models to return for pagination.
     *
     * @var int
     */
    protected $perPage = 25;

    protected $withCount = [
        'products',
    ];

    protected $casts = [
        'automatic_discount_amount' => 'int',
        'line_item_price' => 'int',
        'line_item_discount_amount' => 'int',
        'line_item_tax_amount' => 'int',
        'additional_discount_amount' => 'int',
        'shipping_amount' => 'int',
        'shipping_tax_rate' => 'decimal:4',
        'shipping_tax_amount' => 'int',
        'amount' => 'int',
        'coupon_amount' => 'int',
        'messages' => 'json',
        'order_discount_amount' => 'int',
        'request_data' => 'json',
        'expires_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        parent::deleting(function (self $model) {
            $model->products()->each(function (OrderedProduct $product) {
                $product->delete();
            });
        });
    }

    public function isLinkSent() : bool
    {
        return $this->channel === Channel::LINK_SENT;
    }

    public function isDraft() : bool
    {
        return $this->status === OrderStatus::DRAFT;
    }

    public function requiresPaymentMethod() : bool
    {
        return $this->status === OrderStatus::REQUIRES_PAYMENT_METHOD;
    }

    public function requiresPointOfSaleAction() : bool
    {
        return $this->status === OrderStatus::REQUIRES_POINT_OF_SALES_ACTION;
    }

    public function isCompleted() : bool
    {
        return $this->status === OrderStatus::COMPLETED;
    }

    public function isEditable() : bool
    {
        if (!$this->exists) {
            return true;
        }

        return $this->isDraft() || $this->requiresPointOfSaleAction();
    }

    public function isNotEditable() : bool
    {
        return !$this->isEditable();
    }

    /**
     * @param bool $applyAutomaticDiscount
     * @param bool $demo
     * @param bool $requiresCustomerAction
     *
     * @return $this
     */
    public function checkout(
        bool $applyAutomaticDiscount = false, bool $demo = false, bool $requiresCustomerAction = false
    ) {
        if (!$this->exists) {
            return $this;
        } elseif ($this->isEditable()) {
            if (!$demo) {
                if ($requiresCustomerAction) {
                    $this->status = OrderStatus::REQUIRES_CUSTOMER_ACTION;
                    $this->channel = Channel::LINK_SENT;
                    // how to handle webcheckout and terminal?
                } else {
                    $this->status = OrderStatus::REQUIRES_PAYMENT_METHOD;
                }

                if ($this->products->count() < 0) {
                    App::abort(403, 'You can\'t checkout an order without product.');
                }
            }

            $this->line_item_price = $this->products->sum('price');
            $this->line_item_tax_amount = $this->products->sum('tax_amount');
            $this->line_item_discount_amount = $this->products->sum('discount_amount');

            $this->amount = $this->products->sum('price')
                - $this->automatic_discount_amount
                - $this->additional_discount_amount
                - $this->coupon_amount
                + $this->shipping_amount;

            $tax_setting = TaxSetting::find($this->tax_setting_id);
            if ($tax_setting)
                $this->amount += $this->amount * $tax_setting->rate / 100;

            // todo calculate shipping

            if ($this->amount < 0) {
                $this->amount = 0;
            }

            if (!$demo) {
                $this->save();
            }
        }

        return $this;
    }

    public function getAmount() : int
    {
        return $this->getAttribute('amount');
    }

    public function getChannel() : string
    {
        return $this->getAttribute('channel');
    }

    public function getCurrency() : string
    {
        return $this->getAttribute('currency');
    }

    public function getTotalDiscountAmount() : int
    {
        return $this->automatic_discount_amount
            + $this->line_item_discount_amount
            + $this->additional_discount_amount;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Business\OrderedProduct|\App\Business\OrderedProduct[]
     */
    public function products() : HasMany
    {
        return $this->hasMany(OrderedProduct::class, 'business_order_id', 'id');
    }

    /**
     * @param $attribute
     * @param null $default
     *
     * @return mixed|string|null
     */
    public function display($attribute, $default = null)
    {
        switch ($attribute) {

            case 'customer_address':
                $address = implode(', ', array_filter([
                    $this->customer_street,
                    $this->customer_city,
                    $this->customer_state,
                    $this->customer_country,
                ]));

                if ($address !== '') {
                    return $address.'. '.$this->customer_postal_code;
                }

                break;

            case 'amount':
                return getFormattedAmount($this->currency, $this->amount);

            case 'shipping_amount':
                return getFormattedAmount($this->currency, $this->shipping_amount);

            case 'additional_discount_amount':
                return getFormattedAmount($this->currency, $this->additional_discount_amount);

            case 'automatic_discount_amount':
                return getFormattedAmount($this->currency, $this->automatic_discount_amount);

            case 'line_item_price':
                return getFormattedAmount($this->currency, $this->line_item_price);

            case 'line_item_discount_amount':
                return getFormattedAmount($this->currency, $this->line_item_discount_amount);

            case 'line_item_tax_amount':
                return getFormattedAmount($this->currency, $this->line_item_tax_amount);

            case 'coupon_amount':
                return getFormattedAmount($this->currency, $this->coupon_amount);

            case 'customer_name':

                if ($this->customer_name) {
                    return $this->customer_name;
                }

                break;
        }

        return $default;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany|\App\Business\Charge|\App\Business\Charge[]
     */
    public function charges() : MorphMany
    {
        return $this->morphMany(Charge::class, 'charges', 'business_target_type', 'business_target_id', 'id');
    }

    /**
     * @param bool $rollback
     *
     * @throws \App\Exceptions\HitPayLogicException
     */
    public function updateProductsQuantities(bool $rollback = false)
    {
        if (!$this->exists) {
            return;
        }

        $business = $this->business;
        $orderedProducts = $this->products;

        /**
         * @var \Illuminate\Database\Eloquent\Collection|\App\Business\ProductVariation[] $originalProducts
         */
        $originalProducts = ProductVariation::with('product')
            ->whereIn('id', $orderedProducts->pluck('business_product_id'))->get();

        if ($business->shopify_id) {
            $shopifyAccount = new Shopify($business->shopify_domain, $business->shopify_token);
        }

        $notifiableProducts = [];

        foreach ($orderedProducts as $orderedProduct) {
            /** @var \App\Business\ProductVariation $product */
            $product = $originalProducts->where('id', $orderedProduct->business_product_id)->first();

            if (!$product) {
                continue;
            }

            $quantity = $rollback ? -$orderedProduct->quantity : +$orderedProduct->quantity;

            if ($product->product->isManageable()) {
                $originalQuantity = $product->quantity;

                if (isset($shopifyAccount) && $product->shopify_inventory_item_id) {
                    $response = $shopifyAccount->adjustInventoryLevels([
                        'inventory_item_id' => $product->shopify_inventory_item_id,
                        'location_id' => $business->shopify_location_id,
                        'available_adjustment' => $quantity,
                    ]);

                    $product->update([
                        'quantity' => $response['inventory_level']['available'],
                    ]);
                } elseif ($rollback) {
                    $product->increment('quantity', $orderedProduct->quantity);
                } elseif ($product->quantity - $orderedProduct->quantity >= 0) {
                    $product->decrement('quantity', $orderedProduct->quantity);
                } else {
                    $product->update([
                        'quantity' => 0,
                    ]);
                }

                if (!isset($shopifyAccount)
                    && ($originalQuantity - $orderedProduct->quantity) <= $product->quantity_alert_level) {
                    $notifiableProducts[] = [
                        'product' => $product,
                        'quantity' => $originalQuantity - $orderedProduct->quantity,
                        'quantity_alert_level' => $product->quantity_alert_level,
                    ];
                }
            }

            $hotglueOrderedQuantity = $rollback ? $orderedProduct->quantity : -$orderedProduct->quantity;
            Artisan::queue('update:hotglue-product-quantity --business_id=' . $business->id . ' --product_id=' . $product->id . ' --ordered_quantity=' . $hotglueOrderedQuantity);
        }

        if (count($notifiableProducts)) {
            foreach ($notifiableProducts as $notifiableProduct) {
                switch (true) {

                    case $notifiableProduct['quantity'] === 0;
                        $this->business->notify(new RemindLowQuantity($notifiableProduct['product'], true));

                        break;

                    case $notifiableProduct['quantity'] <= $notifiableProduct['quantity_alert_level']:
                        // if (!Cache::has($notifiableProduct['product']->getKey().'_low_quantity_notified')) {
                        //     Cache::add($notifiableProduct['product']->getKey().'_low_quantity_notified', true, 21600);
                        $this->business->notify(new RemindLowQuantity($notifiableProduct['product']));
                    // }
                }
            }
        }
    }
    public function notifyAboutNewOrder()
    {
        if ($this->exists) {
            $this->notify(new NotifyOrderConfirmation($this));
            $this->business->notify(new NotifyNewCheckoutOrder($this));
        }
    }

    public function notifyAboutStatusChanged(?string $message, bool $isFulfilled = true)
    {
        $this->notify(new NotifyOrderChanges($this, $message, $isFulfilled));
    }

    public function routeNotificationForMail()
    {
        return $this->customer_email;
    }
}
