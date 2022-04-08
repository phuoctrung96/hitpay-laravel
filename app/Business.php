<?php

namespace App;

use App\Business\ApiKey;
use App\Business\BusinessCategory;
use App\Business\BusinessReferral;
use App\Business\BusinessShopSettings;
use App\Business\BusinessUser;
use App\Business\Cashback;
use App\Business\CashbackCampaign;
use App\Business\Charge;
use App\Business\CheckoutCustomisation;
use App\Business\Commission;
use App\Business\Compliance;
use App\Business\ComplianceNotes;
use App\Business\Coupon;
use App\Business\Customer;
use App\Business\Discount;
use App\Business\GatewayProvider;
use App\Business\Image;
use App\Business\Invoice;
use App\Business\Log;
use App\Business\Order;
use App\Business\PaymentCard;
use App\Business\PaymentIntent;
use App\Business\PaymentProvider;
use App\Business\PaymentProviderRate;
use App\Business\PaymentRequest;
use App\Business\Product;
use App\Business\ProductBase;
use App\Business\ProductCategory;
use App\Business\ProductVariation;
use App\Business\SubscriptionPlan;
use App\Business\Role;
use App\Business\Shipping;
use App\Business\ShippingDiscount;
use App\Business\StripeTerminalLocation;
use App\Business\SubscribedEvent;
use App\Business\RecurringBilling;
use App\Business\Tax;
use App\Business\TaxSetting;
use App\Business\Transfer;
use App\Business\Verification;
use App\Business\Wallet\TopUpIntent;
use App\Business\XeroLog;
use App\Enumerations;
use App\Enumerations\Business\Event;
use App\Enumerations\Business\ImageGroup;
use App\Enumerations\Business\NotificationChannel;
use App\Enumerations\Business\PluginProvider;
use App\Enumerations\Business\Wallet\Type;
use App\Enumerations\Image\Size;
use App\Exceptions\ModelNotUpdatableException;
use App\Manager\ApiKeyManager;
use App\Models\Business\BankAccount;
use App\Models\Business\HasPaymentProviders;
use App\Models\Business\PartnerMerchantMapping;
use App\Models\Business\QuickbookIntegration;
use App\Models\BusinessPartner;
use App\Notifications\NotifyAdminAboutNewBusiness;
use App\Notifications\XeroAccountDisconnectedNotification;
use Carbon\Carbon;
use Exception;
use HitPay\Business\Wallet\HasWallet;
use HitPay\Model\UsesUuid;
use HitPay\Stripe\Core;
use HitPay\User\Contracts\Ownable as OwnableContract;
use HitPay\User\Ownable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Throwable;
use App\Enumerations\VerificationStatus;

/**
 * Class Business
 * @package App
 * @property string xero_invoice_grouping
 * @property mixed id
 * @property User owner
 * @property QuickbookIntegration quickbooksIntegration
 * @property ?BusinessPartner partner
 * @property ?BusinessReferral businessReferral
 * @property ?BusinessReferral referredBy
 */
class Business extends Model implements OwnableContract
{
    use HasWallet, HasPaymentProviders, Notifiable, Ownable, SoftDeletes, UsesUuid;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'businesses';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'platform_enabled' => 'bool',
        'auto_pay_to_bank' => 'bool',
        'commission_rate' => 'float',
        'shopify_data' => 'array',
        'migrated' => 'bool',
        'founding_date' => 'date',
        'verified_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'phone_number_verified_at' => 'datetime',
        'banned_at' => 'datetime',
        'verified_wit_my_info_sg' => 'bool',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'country_name',
        'currency_name',
        'is_verified',
        'is_email_verified',
        'is_phone_number_verified',
        'is_banned',
        'is_deactivated',
        'xero_organization_name',
        'xero_sales_account_id',
        'hasXeroPaymentGateway',
        'shop_state',
        'seller_notes',
        'enable_datetime',
        'slots',
        'can_pick_up',
        'thank_message',
        'enabled_shipping',
        'is_redirect_order_completion',
        'url_redirect_order_completion'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'verified_at',
        'email_verified_at',
        'phone_number_verified_at',
        'banned_at',
        'deleted_at',
        'shopSettings'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'identifier',
        'user_id',
        'payment_provider',
        'payment_provider_customer_id',
        'name',
        'display_name',
        'email',
        'phone_number',
        'street',
        'city',
        'state',
        'postal_code',
        'country',
        'introduction',
        'statement_description',
        'website',
        'referred_channel',
        'founding_date',
        'locale',
        'currency',
        'verified_at',
        'email_verified_at',
        'phone_number_verified_at',
        'xero_refresh_token',
        'xero_tenant_id',
        'xero_email',
        'paynow_btn_text',
        'xero_branding_theme',
        'xero_bank_account_id',
        'xero_payment_fee_account_id',
        'xero_payout_account_id',
        'xero_disable_sales_feed',
        'verified_wit_my_info_sg',
        'business_type',
        'merchant_category',
        'partner_id',
        'referred_by_id',
    ];

    /**
     * The relationship counts that should be eager loaded on every query.
     *
     * @var array
     */
    protected $withCount = [
        'shippings',
    ];

    protected $with = [
        'shopSettings'
    ];

    /**
     * The attributes those can be verified.
     *
     * @var array
     */
    private $verifiableAttributes = [
        'email',
        'phone_number',
    ];

    /**
     * The "booting" method of the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::retrieved(function (self $model): void {
            if ($model->platform_key === null) {
                DB::transaction(function () use ($model) {
                    $model->platform_key = Str::lower(implode('-', [
                        Str::orderedUuid()->toString(),
                        str_pad(time(), 12, '0', STR_PAD_LEFT),
                        Str::random(4),
                        Str::random(4),
                        Str::random(4),
                    ]));

                    $model->save();
                }, 3);
            }
        });

        static::creating(function (self $model): void {
            if (!isset($model->attributes['locale'])) {
                $model->setAttribute('locale', App::getLocale());
            }

            if (!isset($model->attributes['migrated'])) {
                $model->setAttribute('migrated', false);
            }

            if (!isset($model->attributes['slug'])) {
                $model->setAttribute('slug', generate_unique_slug($model->getAttribute('name')));
            }

            $model->setAttribute('currency', Core::$countries[$model->getAttribute('country')]['currency']);
        });

        static::updating(function (self $model): void {
            if ($model->isDirty('country')) {
                throw ModelNotUpdatableException::forAttribute($model, 'country');
            }
        });

        static::saving(function (self $model): void {
            $dirties = $model->getDirty();

            foreach ($model->verifiableAttributes as $key) {
                if (empty($dirties[$key])) {
                    $model->setAttribute($key . '_verified_at', null);
                }
            }
        });

        static::created(function (self $model): void {
            $createdAt = $model->getAttribute('created_at');

            $model->createLog('general', 'created', $createdAt);

            // The administrator might able to create an business on behalf after verified the business, the email
            // address and phone number, hence, we will need to log the email verified event here too.
            //
            // NOTE: We are not checking if the email or phone number exists for their verification because we already
            // do it in `static::saving()`, it will set the verification to null if none of them presented.

            foreach ($model->verifiableAttributes as $key) {
                if ($verifiedAt = $model->getAttribute($key . '_verified_at')) {
                    $model->createLog('general', $key . '_verified', $verifiedAt, [
                        $key => $model->getAttributeFromArray($key),
                    ]);
                }
            }

            if ($verifiedAt = $model->getAttribute('verified_at')) {
                $model->createLog('general', 'verified', $verifiedAt);
            }

            try {
                Notification::route('slack', config('services.slack.new_businesses'))
                    ->notify(new NotifyAdminAboutNewBusiness($model));
            } catch (Throwable $exception) {
                // Do nothing if failed.
            }

            $paymentRequest = PaymentRequest::where('business_id', $model->getKey())->where('is_default', true)->first();

            if (!$paymentRequest instanceof PaymentRequest) {
                $paymentRequest = new PaymentRequest();
                $paymentRequest->url = _env_domain('securecheckout', true) . '/payment-request/@' . $model->getAttribute('slug');
                $paymentRequest->amount = 0;
                $paymentRequest->currency = $model->getAttribute('currency');
                $paymentRequest->business_id = $model->getKey();
                $paymentRequest->is_default = true;

                $paymentRequest->save();
            }

            ApiKeyManager::create($model);

            $model->enableAllNotification();

            foreach (Type::toArray() as $value) {
                $model->wallet($value, $model->currency);
            }
        });

        static::updated(function (self $model): void {

            // We can use `static::getChanges()` because the changed attributes has been sync to `static::$changes`.

            $changes = $model->getChanges();

            $original = Arr::only($model->getOriginal(), array_keys($changes));

            $updatedAt = $model->getAttribute('updated_at');

            // This is to prevent error when the original is missing. This scenario will happen when the model is just
            // created and get updated without refreshing the model.

            if ($model->wasRecentlyCreated) {
                foreach ($changes as $key => $value) {
                    if (!array_key_exists($key, $original)) {
                        $original[$key] = null;
                    }
                }
            }

            /////////////////////////////////////////////////////////////////////////////////////////////////////////
            //                                                                                                     //
            // NOTE: The sequence of the following checking is important, it makes the logs looks more reasonable. //
            //                                                                                                     //
            /////////////////////////////////////////////////////////////////////////////////////////////////////////

            // Due to the 'restored' event is fired after the 'saved' event, the changed attributes are already synced
            // to original and we can't trace the original attributes changed in restoring. Therefore, we will log the
            // 'restored' event here.

            if (array_key_exists('deleted_at', $changes)) {
                if (empty($changes['deleted_at'])) {
                    $model->createLog('general', 'restored', $updatedAt);
                }
            }

            // Here we will filter out and get what are the remaining attributes we will want to log as updated.
            //
            // NOTE: Do not include the attributes used in below events, we don't want to create redundant data.

            $updates = Arr::only($changes, [
                'identifier',
                'name',
                'display_name',
                'street',
                'city',
                'state',
                'postal_code',
                'statement_description',
                'founding_date',
            ]);

            if (!empty($updates)) {
                foreach ($updates as $key => $value) {
                    $updates[$key] = [
                        'to' => $value,
                        'from' => $original[$key],
                    ];
                }

                $model->createLog('general', 'updated', $updatedAt, $updates);
            }

            // NOTE: We are not checking if the email or phone number exists for their verification because we already
            // do it in `static::saving()`, it will set the verification to null if none of them presented.

            if (array_key_exists('shopify_id', $changes)) {
                if (empty($original['shopify_id'])) {
                    $model->createLog('general', 'shopify_enabled', $updatedAt, [
                        'shopify_id' => $changes['shopify_id'],
                        'shopify_name' => $changes['shopify_name'] ??
                            $original['shopify_name'] ?? $model->getOriginal('shopify_name'),
                        'shopify_domain' => $changes['shopify_domain'] ??
                            $original['shopify_domain'] ?? $model->getOriginal('shopify_domain'),
                        'shopify_currency' => $changes['shopify_currency'] ??
                            $original['shopify_currency'] ?? $model->getOriginal('shopify_currency'),
                    ]);
                } elseif (empty($changes['shopify_id'])) {
                    $model->createLog('general', 'shopify_disabled', $updatedAt, [
                        'shopify_id' => $original['shopify_id'],
                        'shopify_name' => $original['shopify_name'] ?? $model->getOriginal('shopify_name'),
                        'shopify_domain' => $original['shopify_domain'] ?? $model->getOriginal('shopify_domain'),
                        'shopify_currency' => $original['shopify_currency'] ?? $model->getOriginal('shopify_currency'),
                    ]);
                } else {
                    $model->createLog('general', 'shopify_changed', $updatedAt, [
                        'shopify_id' => [
                            'to' => $changes['shopify_id'],
                            'from' => $original['shopify_id'],
                        ],
                        'shopify_name' => [
                            'to' => $changes['shopify_name'] ??
                                $original['shopify_name'] ?? $model->getOriginal('shopify_name'),
                            'from' => $original['shopify_name'] ?? $model->getOriginal('shopify_name'),
                        ],
                        'shopify_domain' => [
                            'to' => $changes['shopify_domain'] ??
                                $original['shopify_domain'] ?? $model->getOriginal('shopify_domain'),
                            'from' => $original['shopify_domain'] ?? $model->getOriginal('shopify_domain'),
                        ],
                        'shopify_currency' => [
                            'to' => $changes['shopify_currency'] ??
                                $original['shopify_currency'] ?? $model->getOriginal('shopify_currency'),
                            'from' => $original['shopify_currency'] ?? $model->getOriginal('shopify_currency'),
                        ],
                    ]);
                }
            }

            foreach ($model->verifiableAttributes as $key) {
                if (array_key_exists($key, $changes)) {
                    if (empty($original[$key])) {
                        $model->createLog('general', $key . '_added', $updatedAt, [
                            $key => $changes[$key],
                        ]);
                    } elseif (empty($changes[$key])) {
                        $model->createLog('general', $key . '_removed', $updatedAt, [
                            $key => $original[$key],
                        ]);
                    } else {
                        $model->createLog('general', $key . '_changed', $updatedAt, [
                            $key => [
                                'to' => $changes[$key],
                                'from' => $original[$key],
                            ],
                        ]);
                    }
                }

                if (array_key_exists($key . '_verified_at', $changes)) {
                    if (empty($original[$key . '_verified_at']) && isset($changes[$key])) {
                        $model->createLog('general', $key . '_verified', $model->getAttribute($key . '_verified_at'), [
                            $key => $changes[$key],
                        ]);
                    }
                }
            }

            if (array_key_exists('verified_at', $changes)) {
                if (empty($changes['verified_at'])) {
                    $model->createLog('general', 'unverified', $updatedAt);
                } else {
                    $model->createLog('general', 'verified', $model->getAttribute('verified_at'));
                }
            }

            if (array_key_exists('banned_at', $changes)) {
                if (empty($changes['banned_at'])) {
                    $model->createLog('general', 'unbanned', $updatedAt);
                } else {
                    $model->createLog('general', 'banned', $model->getAttribute('banned_at'));
                }
            }
        });

        static::deleted(function (self $model): void {
            if (empty($model->getOriginal('deleted_at'))) {
                $model->createLog('general', 'trashed', $model->getAttribute('deleted_at'));
            }
        });
    }

    /**
     * Get mutator for "is verified" attribute.
     *
     * @return bool
     */
    public function getIsVerifiedAttribute(): bool
    {
        return !is_null($this->verified_at);
    }

    /**
     * Get mutator for "is email verified" attribute.
     *
     * @return bool
     */
    public function getIsEmailVerifiedAttribute(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Get mutator for "is phone number verified" attribute.
     *
     * @return bool
     */
    public function getIsPhoneNumberVerifiedAttribute(): bool
    {
        return !is_null($this->phone_number_verified_at);
    }

    /**
     * Get mutator for "is banned" attribute.
     *
     * @return bool
     */
    public function getIsBannedAttribute(): bool
    {
        return !is_null($this->banned_at);
    }

    /**
     * Get mutator for "is deactivated" attribute.
     *
     * @return bool
     */
    public function getIsDeactivatedAttribute(): bool
    {
        return $this->trashed();
    }

    /**
     * Get mutator for preferred locale of the entity.
     *
     * @return string
     */
    public function preferredLocale(): string
    {
        return $this->locale;
    }

    /**
     * Get the business shop settings.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne|\App\Business\BusinessShopSettings|\App\Business\BusinessShopSettings
     */
    public function shopSettings(): HasOne
    {
        return $this->hasOne(BusinessShopSettings::class, 'business_id', 'id');
    }

    /**
     * Get the business refund campaign.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne|\App\Business\CashbackCampaign|\App\Business\CashbackCampaign
     */
    public function cashbackCampaigns(): HasOne
    {
        return $this->hasOne(CashbackCampaign::class, 'campaign_business_id', 'id');
    }

    /**
     * Get the business category.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Business\BusinessCategory|\App\Business\BusinessCategory
     */
    public function merchantCategory()
    {
        return $this->belongsTo(BusinessCategory::class, 'merchant_category', 'id');
    }

    /**
     * Get the bank accounts.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Models\Business\BankAccount|\App\Models\Business\BankAccount[]
     */
    public function bankAccounts() : HasMany
    {
        return $this->hasMany(BankAccount::class, 'business_id', 'id');
    }

    /**
     * Get the charges.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Business\Charge|\App\Business\Charge[]
     */
    public function charges(): HasMany
    {
        return $this->hasMany(Charge::class, 'business_id', 'id');
    }

    public function platformCharges(): HasMany
    {
        return $this->hasMany(Charge::class, 'platform_business_id', 'id');
    }

    /**
     * Get the transfers.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Business\Transfer|\App\Business\Transfer[]
     */
    public function transfers(): HasMany
    {
        return $this->hasMany(Transfer::class, 'business_id', 'id');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class, 'business_id', 'id');
    }

    /**
     * Get the payment intents.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Business\PaymentIntent|\App\Business\PaymentIntent[]
     */
    public function paymentIntents(): HasMany
    {
        return $this->hasMany(PaymentIntent::class, 'business_id', 'id');
    }

    /**
     * Get the top up intents.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function topUpIntents(): HasMany
    {
        return $this->hasMany(TopUpIntent::class, 'business_id', 'id');
    }

    /**
     * Get the customers.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Business\Customer|\App\Business\Customer[]
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'business_id', 'id');
    }

    /**
     * Get the discounts.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Business\Discount|\App\Business\Discount[]
     */
    public function discounts(): HasMany
    {
        return $this->hasMany(Discount::class, 'business_id', 'id');
    }

    /**
     * Get the coupons.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Business\Coupon|\App\Business\Coupon[]
     */
    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class, 'business_id', 'id');
    }

    /**
     * Get the cashbacks.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne|\App\Business\Cashback|\App\Business\Cashback[]
     */
    public function cashbacks(): HasOne
    {
        return $this->hasOne(Cashback::class, 'business_id', 'id');
    }

    /**
     * Get the compliance.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne|\App\Business\Cashback|\App\Business\Cashback[]
     */
    public function compliance(): HasOne
    {
        return $this->hasOne(Compliance::class, 'business_id', 'id');
    }

    /**
     * Get the compliance notes.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne|\App\Business\ComplianceNotes|\App\Business\ComplianceNotes[]
     */
    public function complianceNotes(): HasOne
    {
        return $this->hasOne(ComplianceNotes::class, 'business_id', 'id');
    }

    /**
     * Get shipping discount.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function shipping_discount(): HasOne
    {
        return $this->hasOne(ShippingDiscount::class, 'business_id', 'id');
    }

    /**
     * Get the images.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function images(): HasMany
    {
        return $this->hasMany(Image::class, 'business_id', 'id');
    }

    /**
     * Get the paymentRequests.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Business\PaymentRequest|\App\Business\PaymentRequest[]
     */
    public function paymentRequests(): HasMany
    {
        return $this->hasMany(PaymentRequest::class, 'business_id', 'id');
    }

    /**
     * Get the logo.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function logo(): HasOne
    {
        return $this->hasOne(Image::class, 'business_id', 'id')->where('group', ImageGroup::LOGO);
    }

    /**
     * Get the logs.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Business\Log|\App\Business\Log[]
     */
    public function logs(): HasMany
    {
        return $this->hasMany(Log::class, 'business_id', 'id');
    }

    /**
     * Get the orders.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Business\Order|\App\Business\Order[]
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'business_id', 'id');
    }

    /**
     * Get tax settings.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Business\TaxSetting|\App\Business\TaxSetting[]
     */
    public function tax_settings(): HasMany
    {
        return $this->hasMany(TaxSetting::class, 'business_id', 'id');
    }

    /**
     * Get the payment cards.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Business\PaymentCard|\App\Business\PaymentCard[]
     */
    public function paymentCards(): HasMany
    {
        return $this->hasMany(PaymentCard::class, 'business_id', 'id');
    }

    /**
     * Get the product categories.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Business\ProductCategory|\App\Business\ProductCategory[]
     */
    public function productCategories(): HasMany
    {
        return $this->hasMany(ProductCategory::class, 'business_id', 'id');
    }

    /**
     * Get the products.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Business\Product|\App\Business\Product[]
     */
    public function productBases(): HasMany
    {
        return $this->hasMany(ProductBase::class, 'business_id', 'id');
    }

    /**
     * Get the products.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Business\Product|\App\Business\Product[]
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'business_id', 'id');
    }

    /**
     * Get the product variations.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Business\ProductVariation|\App\Business\ProductVariation[]
     */
    public function productVariations(): HasMany
    {
        return $this->hasMany(ProductVariation::class, 'business_id', 'id');
    }

    /**
     * Get the recurring plan templates.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Business\SubscriptionPlan|\App\Business\SubscriptionPlan[]
     */
    public function subscriptionPlans(): HasMany
    {
        return $this->hasMany(SubscriptionPlan::class, 'business_id', 'id');
    }

    /**
     * Get the subscribed recurring plans.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Business\RecurringBilling|\App\Business\RecurringBilling[]
     */
    public function recurringBillings(): HasMany
    {
        return $this->hasMany(RecurringBilling::class, 'business_id', 'id');
    }

    /**
     * Get invoices
     *
     * @return HasMany
     */
    public function invoices() : HasMany
    {
        return $this->hasMany(Invoice::class, 'business_id', 'id');
    }

    /**
     * Get the roles.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Business\Role|\App\Business\Role[]
     */
    public function roles(): HasMany
    {
        return $this->hasMany(Role::class, 'business_id', 'id');
    }

    /**
     * Get the shippings.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Business\Shipping|\App\Business\Shipping[]
     */
    public function shippings(): HasMany
    {
        return $this->hasMany(Shipping::class, 'business_id', 'id');
    }

    /**
     * Get the subscribed features.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\SubscribedFeature|\App\SubscribedFeature[]
     */
    public function subscribedFeatures(): HasMany
    {
        return $this->hasMany(SubscribedFeature::class, 'business_id', 'id');
    }

    /**
     * Get the taxes.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Business\Tax|\App\Business\Tax[]
     */
    public function taxes(): HasMany
    {
        return $this->hasMany(Tax::class, 'business_id', 'id');
    }

    /**
     * Get the shippings.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Business\Shipping|\App\Business\Shipping[]
     */
    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class, 'business_id', 'id');
    }

    /**
     * Get the shippings.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Business\Shipping|\App\Business\Shipping[]
     */
    public function gatewayProviders(): HasMany
    {
        return $this->hasMany(GatewayProvider::class, 'business_id', 'id');
    }

    /**
     * Get the statement description for charge.
     *
     * @return string|null
     */
    public function statementDescription(string $append = null): ?string
    {
        $statementDescriptor = $this->statement_description ?? $this->getName() ?? Facades\Config::get('app.name');

        if (is_string($append)) {
            $statementDescriptor .= $append;
        }

        $statementDescriptor = preg_replace("/[^a-zA-Z0-9]+/", '', $statementDescriptor);
        $statementDescriptor = Str::limit($statementDescriptor, 22, '');
        $statementDescriptor = trim($statementDescriptor);

        if (Str::length($statementDescriptor) === 0) {
            $statementDescriptor = 'HitPay';
        }

        return $statementDescriptor;
    }

    /**
     * Get checkout customisation settings.
     *
     * @return \App\Business\CheckoutCustomisation
     */
    public function checkoutCustomisation(): CheckoutCustomisation
    {
        $customisation = $this->hasOne(CheckoutCustomisation::class, 'business_id', 'id')->get();

        if ($customisation->isEmpty()) {
          $customisation = new CheckoutCustomisation;
          $customisation->business_id = $this->attributes['id'];
          $customisation->tint_color = '#4A90E2';
          $customisation->save();
          // refresh id & default values
          $customisation->refresh();
          return $customisation;
        } else {
          $cust = $customisation->first();
          $cust->payment_order = json_decode($cust->payment_order);
          $cust->method_rules = json_decode($cust->method_rules);
          return $cust;
        }
    }

    public function updateCustomisation($data)
    {
        $customisation = $this->hasOne(CheckoutCustomisation::class, 'business_id', 'id')->get();

        if (!$customisation->isEmpty()) {
            $customisation = $customisation->first();

            if (array_key_exists('theme', $data)) {
              $customisation->theme = $data['theme'];
              $customisation->tint_color = $data['customColor'];
            }

            if (array_key_exists('payment_order', $data)) {
              $customisation->payment_order = $data['payment_order'];
            }

            if (array_key_exists('method_rules', $data)) {
              $customisation->method_rules = $data['method_rules'];
            }

            $customisation->save();
        }
    }

    public function getStoreCustomisationStyles()
    {
        $customisation = $this->checkoutCustomisation();
        $style = array();
        switch ($customisation->theme) {
            case "hitpay":
                $style['main_color'] = '#011B5F';
                $style['main_text_color'] = 'white';
                $style['button_color'] = '#011B5F';
                $style['button_text_color'] = 'white';
                break;
            case "light":
                $style['main_color'] = '#797979';
                $style['main_text_color'] = 'white';
                $style['button_color'] = '#011B5F';
                $style['button_text_color'] = 'white';
                break;
            case "custom":
                $style['main_color'] = $customisation->tint_color;
                $style['main_text_color'] = $this->getTextColor($customisation->tint_color);
                $style['button_color'] = $customisation->tint_color;
                $style['button_text_color'] = $this->getTextColor($customisation->tint_color);;
                break;
            default:
                $style['main_color'] = '#011B5F';
                $style['main_text_color'] = 'white';
                $style['button_color'] = '#011B5F';
        }

        return $style;
    }

    // Filter business object and return only props that are used in checkout FE
    public function getFilteredData () {
      return [
        'id' => $this->id,
        'name' => $this->name,
        'country' => $this->country,
        'currency' => $this->currency
      ];
    }

    public function logoUrl()
    {
        $logo = $this->logo()->first();
        return $logo ? $logo->getUrl(Size::SMALL) : '';
    }

    public function getName()
    {
        $name = $this->display_name ?? $this->name;

        if ($this->trashed()) {
            return $name . ' (Deleted)';
        }

        return $name;
    }

    public function getAddress()
    {
        return implode(', ', array_filter([
            $this->street,
            $this->city,
            $this->state,
            $this->country,
        ]));
    }

    public function getCountryNameAttribute()
    {
        if (Lang::has('misc.country.' . $this->attributes['country'])) {
            return Lang::get('misc.country.' . $this->attributes['country']);
        }

        return $this->attributes['country'];
    }

    public function getCurrencyNameAttribute()
    {
        if (Lang::has('misc.currency.' . $this->attributes['currency'])) {
            return Lang::get('misc.currency.' . $this->attributes['currency']);
        }

        return $this->attributes['currency'];
    }

    /**
     * Indicate if business is banned.
     *
     * @return bool
     */
    public function isBanned(): bool
    {
        return $this->is_banned;
    }

    /**
     * Indicate if business is deactivated.
     *
     * @return bool
     */
    public function isDeactivated(): bool
    {
        return $this->is_deactivated;
    }

    /**
     * Overridden to disable hard deletion on a soft deleted model.
     *
     * @throws \Exception
     */
    public function forceDelete(): void
    {
        throw new Exception('This method has been overridden to disable hard deletion on a soft deleted model.');
    }

    /**
     * Create log for the business's activity.
     *
     * @param string $group
     * @param string $event
     * @param \Illuminate\Support\Carbon $loggedAt
     * @param array|null $attributes
     *
     * @throws \Exception
     */
    protected function createLog(string $group, string $event, Carbon $loggedAt, array $attributes = null): void
    {
        $log = $this->logs()->make([
            'group' => $group,
            'event' => $event,
            'logged_at' => $loggedAt,
        ]);

        if ($attributes) {
            $log->logAttributes($attributes);
        }

        $log->save();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\StripeTerminal|\App\StripeTerminal[]
     */
    public function stripeTerminalLocations(): HasMany
    {
        return $this->hasMany(StripeTerminalLocation::class, 'business_id', 'id');
    }

    public function stripeTerminals(): HasManyThrough
    {
        return $this->hasManyThrough(StripeTerminal::class, StripeTerminalLocation::class,
            'business_id', 'business_stripe_terminal_location_id', 'id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Business\SubscribedEvent|\App\Business\SubscribedEvent[]
     */
    public function subscribedEvents(): HasMany
    {
        return $this->hasMany(SubscribedEvent::class, 'business_id', 'id');
    }

    /**
     * @param $refreshToken
     * @param $tenantID
     * @return bool
     */
    public function saveXeroInfo($refreshToken, $tenantID, $email)
    {
        if (!(strlen($refreshToken)) || !strlen($tenantID)) {
            return false;
        }
        $this->xero_refresh_token = $refreshToken;
        $this->xero_tenant_id = $tenantID;
        $this->xero_email = $email;
        $this->update();

        return true;
    }

    public function routeNotificationForMail()
    {
        return $this->email ?? $this->owner->email;
    }

    /**
     * Route notifications for the Firebase channel.
     *
     * @param \Illuminate\Notifications\Notification $notification
     *
     * @return array
     */
    public function routeNotificationForFirebase($notification)
    {
        return $this->owner->routeNotificationForFirebase($notification);
    }

    public function xeroLogs()
    {
        return $this->hasMany(XeroLog::class, 'business_id');
    }

    public function getProviderMethods(string $provider)
    {
        $provider = $this->gatewayProviders()->where('name', $provider)->first();
        $paymentMethods = [];

        foreach ($provider->array_methods as $method) {
            $paymentMethods[] = $method;
        }

        return $paymentMethods;
    }

    public function client(): HasMany
    {
        return $this->hasMany(Passport::clientModel(), 'business_id', 'id');
    }

    /**
     * @return string
     */
    public function getLastXeroSyncDate()
    {
        if (!empty($this->xero_last_sync_date)) {
            return $this->xero_last_sync_date;
        }

        return $this->xero_sync_date;
    }

    public function xeroOrganizations(): HasMany
    {
        return $this->hasMany(XeroOrganization::class);
    }

    public function disconnectXero(): void
    {
        $this->xero_email = null;
        $this->xero_refresh_token = null;
        $this->xero_tenant_id = null;
        $this->xero_sync_date = null;
        $this->xero_last_sync_date = null;
        $this->xero_refund_account_type = null;
        $this->xero_fee_account_type = null;
        $this->xero_sales_account_type = null;
        $this->xero_last_sync_date = null;
        $this->xero_fee_account_id = null;
        $this->xero_refund_account_id = null;
        $this->xero_account_id = null;
        $this->xero_contact_id = null;
        $this->xero_bank_account_id = null;
        $this->xero_invoice_grouping = null;
        $this->xero_payment_fee_account_id = null;
        $this->xero_payout_account_id = null;
        $this->save();

        $this->xeroLogs()->delete();
        $this->xeroOrganizations()->delete();

        $this->owner->notify(new XeroAccountDisconnectedNotification($this));
    }

    public function getXeroOrganizationNameAttribute()
    {
        return optional($this->xeroOrganizations->first())->name;
    }

    public function getXeroSalesAccountIdAttribute()
    {
        return $this->xero_account_id;
    }

    public function canSyncWithXero(): bool
    {
        return
            !$this->xero_disable_sales_feed &&
            !empty($this->xero_tenant_id) &&
            !empty($this->xero_account_id) &&
            !empty($this->xero_fee_account_id)
            ;
    }

    public function getHasXeroPaymentGatewayAttribute()
    {
        return $this->gatewayProviders()->where('name', 'xero')->exists();
    }

    public function getSellerNotesAttribute() {
        return $this->shopSettings->seller_notes ?? '';
    }

    public function getShopStateAttribute() {
        return $this->shopSettings->shop_state ?? 1;
    }

    public function getEnableDatetimeAttribute() {
        return $this->shopSettings->enable_datetime ?? null;
    }

    public function getSlotsAttribute() {
        return $this->shopSettings->slots ?? null;
    }

    public function getCanPickUpAttribute() {
        return $this->shopSettings->can_pick_up ?? 1;
    }

    public function getThankMessageAttribute() {
        return $this->shopSettings->thank_message ?? '';
    }

    public function getEnabledShippingAttribute() {
        return $this->shopSettings->enabled_shipping ?? 1;
    }

    public function getIsRedirectOrderCompletionAttribute() {
        return $this->shopSettings->is_redirect_order_completion ?? 1;
    }

    public function getUrlRedirectOrderCompletionAttribute() {
        return $this->shopSettings->url_redirect_order_completion ?? '';
    }

    private function getTextColor($hexColor)
    {

        // hexColor RGB
        $R1 = hexdec(substr($hexColor, 1, 2));
        $G1 = hexdec(substr($hexColor, 3, 2));
        $B1 = hexdec(substr($hexColor, 5, 2));

        // Black RGB
        $blackColor = "#000000";
        $R2BlackColor = hexdec(substr($blackColor, 1, 2));
        $G2BlackColor = hexdec(substr($blackColor, 3, 2));
        $B2BlackColor = hexdec(substr($blackColor, 5, 2));

        // Calc contrast ratio
        $L1 = 0.2126 * pow($R1 / 255, 2.2) +
            0.7152 * pow($G1 / 255, 2.2) +
            0.0722 * pow($B1 / 255, 2.2);

        $L2 = 0.2126 * pow($R2BlackColor / 255, 2.2) +
            0.7152 * pow($G2BlackColor / 255, 2.2) +
            0.0722 * pow($B2BlackColor / 255, 2.2);

        $contrastRatio = 0;
        if ($L1 > $L2) {
            $contrastRatio = (int)(($L1 + 0.05) / ($L2 + 0.05));
        } else {
            $contrastRatio = (int)(($L2 + 0.05) / ($L1 + 0.05));
        }

        // If contrast is more than 5, return black color
        if ($contrastRatio > 5) {
            return '#000000';
        } else {
            // if not, return white color.
            return '#FFFFFF';
        }
    }

    public function verifications() : HasMany
    {
        return $this->hasMany(Verification::class, 'business_id', 'id');
    }

    public function verifiedData() : HasOne
    {
        return $this->hasOne(Verification::class, 'business_id', 'id')
            ->whereNotNull('verified_at')
            ->orderByDesc('verified_at');
    }

    public function businessVerified() : bool
    {
        return ($this->verifications()
          ->where('type', 'business')
          ->whereIn('status', [VerificationStatus::VERIFIED, VerificationStatus::MANUAL_VERIFIED])
          ->count() > 0);
    }

    public function enableAllNotification() : void
    {
        $this->subscribedEvents()->delete();

        $events = Event::collection()->map(function (array $event) {
            return new SubscribedEvent([
                'event' => $event['value'],
                'channel' => NotificationChannel::EMAIL,
            ]);
        })->merge(Event::collection([
            Event::getConstantByValue(Event::DAILY_PAYOUT),
        ])->map(function (array $event) {
            return new SubscribedEvent([
                'event' => $event['value'],
                'channel' => NotificationChannel::PUSH_NOTIFICATION,
            ]);
        }));

        $this->subscribedEvents()->saveMany($events->all());
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role_id');
    }

    public function businessUsers(): HasMany
    {
        return $this->hasMany(BusinessUser::class);
    }

    public function quickbooksIntegration(): HasOne
    {
        return $this->hasOne(QuickbookIntegration::class);
    }

    /**
     * Get the cover image.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function coverImage(): HasOne
    {
        return $this->hasOne(Image::class, 'business_id', 'id')->where('group', ImageGroup::COVER);
    }

    public function businessPartner(): BelongsTo
    {
        return $this->belongsTo(BusinessPartner::class, 'partner_id');
    }

    public function partner(): HasOneThrough
    {
        return $this->hasOneThrough(
            BusinessPartner::class,
            PartnerMerchantMapping::class,
            'business_id',
            'id',
            'id',
            'business_partner_id'
        );
    }

    public function getRegularCashback(Charge $charge, $display = false){
        $plugin_channel = $charge->plugin_provider;
        $channel = PluginProvider::getProviderByChanel($plugin_channel);

        $cashback = $this->cashbacks();

        if (!$display)
            $cashback = $cashback->where('payment_provider_charge_type', $charge->payment_provider_charge_method);

        $cashback = $cashback->where('enabled', 1)
            ->where('minimum_order_amount', '<=', $charge->amount)
            ->where(function($query) {
                $query->whereNull('ends_at');
                $query->orWhere('ends_at', '>', Carbon::now());
            })
            ->get();
        if (!is_null($cashback->first()) && $cashback->first()->channel != 'all')
            $cashback = $cashback->where('channel', '=', $channel);

        return $cashback;
    }

    function allowProvider ($provider) {
      switch (config('services.' . $provider . '.enabled', 'none')) {
        case 'none':
          return false;

        case 'all_users':
          return true;

        case 'whitelist_only':
          $whitelist = explode(',', config('services.' . $provider . '.whitelist'));
          return in_array($this->id, $whitelist);

        default:
          return false;
      }
    }

    public function allowGrabPay () {
      return $this->allowProvider('grabpay');
    }

    public function allowZip () {
      return $this->allowProvider('zip');
    }

    public function allowShopee () {
      return $this->allowProvider('shopee');
    }

    public function businessReferral():HasOne
    {
        return $this->hasOne(BusinessReferral::class);
    }

    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(BusinessReferral::class, 'referred_by_id');
    }

    public function shopifyStores(): HasMany
    {
        return $this->hasMany(BusinessShopifyStore::class, 'business_id', 'id');
    }
}
