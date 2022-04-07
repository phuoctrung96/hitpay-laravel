<?php

namespace App\Business;

use App\Business;
use App\Enumerations\Business\InvoiceStatus;
use App\Enumerations\Business\PaymentRequestStatus;
use App\Helpers\Currency;
use App\Http\Resources\Business\Product as ProductResource;
use HitPay\Business\Ownable;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use App\Http\Resources\Business\ProductVariation as ProductVariationResource;

/**
 * Class Invoice
 * @package App\Business
 *
 * @property string $email
 * @property string $user_id
 * @property string $business_customer_id
 * @property string $payment_request_id
 * @property float $amount
 * @property string $currency
 * @property string $reference
 * @property string $status
 * @property Customer $customer
 * @property PaymentRequest $paymentRequest
 * @property string $due_date
 * @property string $invoice_date
 * @property Business $business
 */
class Invoice extends Model
{
    use Notifiable;
    use Ownable;
    use UsesUuid;

    /**
     * @var string
     */
    protected $table = 'business_invoices';

    protected $fillable = [
        'business_customer_id', 'reference', 'invoice_number', 'email', 'currency', 'amount',
        'amount_no_tax', 'products','memo', 'tax_settings_id', 'due_date', 'invoice_date',
        'status', 'payment_request_id', 'balance_amount', 'attached_file',
        'allow_partial_payments',
    ];

    const ENABLE_PARTIAL_PAYMENT = 1;
    const DISABLE_PARTIAL_PAYMENT = 0;

    /**
     * The number of models to return for pagination.
     *
     * @var int
     */
    protected $perPage = 25;

    /**
     * The "booting" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::retrieved(function (self $model): void {
            $products = json_decode($model->products) ? json_decode($model->products) : null;

            $added_products = [];

            if ($products) {
                foreach ($products as $product) {
                    $productVariation = $model->business->productVariations()->with('product')->find($product->variation_id);

                    if ($productVariation) {
                        $added_products[] = [
                            'product' => new ProductResource($productVariation->product),
                            'variation' => new ProductVariationResource($productVariation),
                            'quantity' => $product->quantity,
                            'discount' => $product->discount ?? 0
                        ];
                    }
                }
            }

            $model->products = $added_products;
        });

        static::saving(function (self $model): void {
            if ($model->products && is_array($model->products)){

                $added_products = [];

                foreach ($model->products as $product) {
                    array_push($added_products, ['variation_id' => $product['variation']['id'], 'quantity' => $product['quantity'], 'discount' => $product['discount']]);
                }

                $model->products = json_encode($added_products);
            }

        });
    }

    /**
     * @return Customer
     */
    public function customer() : BelongsTo
    {
        return $this->belongsTo(Customer::class, 'business_customer_id', 'id', 'customer');
    }

    /**
     * @return PaymentRequest
     */
    public function paymentRequest() : HasOne
    {
        return $this->hasOne(PaymentRequest::class, 'id', 'payment_request_id');
    }

    /**
     * @return InvoicePartialPaymentRequest
     */
    public function invoicePartialPaymentRequests() : HasMany
    {
        return $this->hasMany(InvoicePartialPaymentRequest::class, 'invoice_id', 'id');
    }

    /**
     * @return Business
     */
    public function business() : BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id', 'id', 'business');
    }

    /**
     * @return mixed|string
     */
    public function routeNotificationForMail()
    {
        return $this->customer->email ?? $this->email;
    }

    /**
     * Mark an invoice as paid
     */
    public function setAsPaid()
    {
        $this->status = InvoiceStatus::PAID;
        $this->save();
    }

    /**
     * @return HasMany
     */
    public function charges() : HasMany
    {
        return $this->hasMany(Charge::class, 'plugin_provider_reference', 'payment_request_id');
    }

    /**
     * @return HasOne
     */
    public function tax_setting() : HasOne
    {
        return $this->hasOne(TaxSetting::class, 'id', 'tax_settings_id');
    }

    /**
     * @return bool
     */
    public function isOverdue()
    {
        return (!empty($this->due_date)
            && now() >= $this->due_date
            && $this->status != \App\Enumerations\Business\InvoiceStatus::PAID);
    }

    /***
     * @return bool
     */
    public function isPartialityPaid()
    {
        if ($this->allow_partial_payments === self::DISABLE_PARTIAL_PAYMENT) {
            // not partial payment set
            return false;
        }

        if ($this->amount == $this->balance_amount) {
            // not yet paid
            return false;
        }

        if ($this->balance_amount == 0) {
            // should be paid status
            return false;
        }

        return true;
    }

    /**
     * @return mixed
     */
    public static function getOverdueInvoices()
    {
        return Invoice::where('due_date', '!=', null)
            ->where('due_date', '<=', now())
            ->whereIn('status', [InvoiceStatus::SENT, InvoiceStatus::PENDING])
            ->get();
    }

    /***
     * @return string
     */
    public function getStatusLabel()
    {
        if ($this->isOverdue()) {
            return "Overdue";
        } else {
            if ($this->isPartialityPaid()) {
                return "Partiality Paid";
            } else {
                switch ($this->status) {
                    case (InvoiceStatus::PAID): return "Paid";
                    case (InvoiceStatus::PENDING): return "Sent";
                    case (InvoiceStatus::SENT): return "Sent";
                    default: return $this->status;
                }
            }
        }
    }

    /***
     * @return string
     */
    public function getCustomStatus()
    {
        if ($this->isOverdue()) {
            return InvoiceStatus::OVERDUE;
        } else {
            if ($this->isPartialityPaid()) {
                return InvoiceStatus::PARTIALITY_PAID;
            } else {
                switch ($this->status) {
                    case (InvoiceStatus::PAID): return InvoiceStatus::PAID;;
                    case (InvoiceStatus::PENDING): return InvoiceStatus::SENT;
                    case (InvoiceStatus::SENT): return InvoiceStatus::SENT;
                    default: return $this->status;
                }
            }
        }
    }
}
