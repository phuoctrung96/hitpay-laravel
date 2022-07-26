<?php

namespace App\Business;

use App\Business;
use App\Enumerations\Business\InvoiceStatus;
use App\Helpers\Currency;
use App\Http\Resources\Business\Product as ProductResource;
use HitPay\Business\Ownable;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use phpDocumentor\Reflection\Types\This;

class InvoicePartialPaymentRequest extends Model
{
    use Notifiable;
    use Ownable;
    use UsesUuid;

    /**
     * @var string
     */
    protected $table = 'business_invoice_partial_payment_requests';

    protected $fillable = ['amount','payment_request_id', 'due_date'];

    protected $casts = [
        'due_date' => 'datetime',
    ];

    public function getDueDateAttribute($value)
    {
        if ($value) {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        }
    }

    /**
     * The number of models to return for pagination.
     *
     * @var int
     */
    protected $perPage = 25;

    /**
     * @return Invoice
     */
    public function invoice() : BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'id');
    }

    /**
     * @return PaymentRequest
     */
    public function paymentRequest() : HasOne
    {
        return $this->hasOne(PaymentRequest::class, 'id', 'payment_request_id');
    }

}
