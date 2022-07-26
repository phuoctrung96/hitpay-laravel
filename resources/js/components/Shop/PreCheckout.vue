<style scoped>
/*.custom-input {
    display: block;
    outline: none;
    border: none;
    border-radius: 0;
    width: 100%;
    height: 15px;
}*/

.select-custom {
    border-radius: 6px;
    outline: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    text-indent: 1px;
    text-overflow: '';
    text-align-last: center;
}

.self-pick-btn {
    border: 1px solid dimgrey;
    background-color: white;
    margin-left: -10px;
    border-radius: 0px 6px 6px 0px;
    text-align: center;
    z-index: 1;
    position: relative;
}

.self-pick-btn {
    cursor: pointer;
}

.select-custom:hover {
    cursor: pointer;
}

.price-box {
    width: 20%;
}

.radio-toolbar {
    padding: 0px 0px 2px 0px;
}

.shipping-option {
    padding: 25px 0px 0px;
}

.l-precart h1.p-title {
    font-size: 28px;
    padding: 18px 0px 31px;
    text-align: center;
}

.l-precart .frm-group h6.p-title {
    font-size: 16px;
    margin: 0px 0px 20px;
}

.frm-group .form-control {
    border: 1px solid #D4D6DD;
    min-height: 48px;
    border-radius: 0;
}

.frm-group .form-control.field-required {
    border: 1px solid red;
}

.frm-group .form-control:focus {
    outline: none;
    box-shadow: none;
}

/*.form-group.is-border{
    border: 1px solid #D4D6DD;
    padding:  5px 10px;
}*/

/*.form-group.is-border label{
    font-size: 13px;
    color: #6D6E73;
    margin: 0;
    display: block;
}*/
.frm-w-btn{
    margin: 0 0 20px 0;
}
.frm-w-btn .form-control {
    width: calc(100% - 105px);
    display: block;
    float: left;
}

.frm-w-btn .btn-normal {
    width: 105px;
    display: block;
    float: left;
}

.checkout-footer {
    padding: 50px 0px 0px;
}

.checkout-footer .checkout-pay-button {
    font-size: 16px;
}

.r-precart .item {
    border-bottom: 1px solid #D4D6DD;
    padding: 0px 0px 27px;
    margin: 0px 0px 38px;
}

.r-precart .item:last-child {
    margin: 0px 0px 22px;
}

.r-precart .item .p-title {
    font-size: 18px;
    color: #1E1E1F;
    margin: 0px 0px 7px;
}

.r-precart .item .variation-name {
    color: #6D6E73;
    font-size: 16px;
}

.r-precart .ctn-inner {
    padding: 13px 20px 17px 20px;
    background: #F5F5F5;
}

.checkout-product {
    position: relative;
    padding: 0px 0px 26px;
}

.checkout-product span {
    position: absolute;
    right: -5px;
    top: -15px;
    width: 27px;
    height: 27px;
    border-radius: 50%;
    background: #87888A;
    text-align: center;
    color: #FFF;
    font-size: 14px;
    font-weight: 600;
    padding: 2px 0px 0px;
}

.checkout-product .img-precheckout {
    max-width: 84px;
    height: auto;
    border: 1px solid #D4D6DD;
}

.meta-price {
    color: #6D6E73;
    font-size: 15px;
    padding: 0px 0px 11px;
}

.meta-price .price {
    color: #1E1E1F;
    font-size: 16px;
    font-weight: 500;
}

.cart-total {
    padding: 0px 0px 11px;
}

.total-price {
    color: #6D6E73;
    font-size: 15px;
    font-weight: 500;
    border-top: 1px solid #D4D6DD;
    padding: 20px 0px 0px;
}

.total-price .price {
    color: #1E1E1F;
    font-size: 20px;
    font-weight: 500;
}

.shipping-list {
    margin-left: 30px;
}

/*.date-time .item:last-child .label-checkbox{
    margin-bottom: 0px;
}*/

/* Customize the radio checkbox */
.label-checkbox {
    display: block;
    position: relative;
    padding-left: 32px;
    margin-bottom: 22px;
    cursor: pointer;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
}

.label-checkbox input {
    position: absolute;
    opacity: 0;
    cursor: pointer;
    height: 0;
    width: 0;
}

.checkmark {
    position: absolute;
    top: 50%;
    left: 0;
    height: 24px;
    width: 24px;
    border: 1px solid #D4D6DD;
    border-radius: 50%;
    margin-top: -12px;
}

.label-checkbox .checkmark:after {
    top: 5px;
    left: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.label-checkbox:hover input ~ .checkmark:after {
    /*background-color: #CCC;*/
}

.label-checkbox input:checked ~ .checkmark {
    background-color: #FFF;
}

.checkmark:after {
    content: "";
    position: absolute;
    display: none;
}

.label-checkbox input:checked ~ .checkmark:after {
    display: block;
}

.delivery-information {
    padding: 20px 0px 0px;
}

.frm-group .form-group {
    margin: 0px 0px 16px;
}

.delivery-information .form-group .form-control {
    padding: 5px 15px 5px 15px;
}

.delivery-information .form-text {
    margin: 0px 0px 5px;
}

.delivery-information .form-group textarea.form-control {
    min-height: 80px;
    padding: 14px 15px 14px 15px;
}

@media (min-width: 768px) {
    .ct-pre-checkout .r-precart:after {
        position: absolute;
        left: 15px;
        top: -100%;
        bottom: 0;
        width: 300%;
        content: "";
        display: block;
        background: #fafafa;
        background-position: left top;
        z-index: -1;
    }

    .ct-pre-checkout .r-precart {
        position: relative;
        z-index: 20;
    }

    .r-precart .ctn-inner {
        background: none;
        padding: 0px 0px 50px 50px;
    }

    .l-precart {
        padding-bottom: 120px;
    }

    .l-precart h1.p-title {
        font-size: 40px;
        padding: 63px 0px 54px;
        text-align: left;
    }

    .shipping-option {
        padding: 31px 0px 0px;
    }

    .l-precart .frm-group h6.p-title {
        font-size: 18px;
    }

    /*.form-group.is-border{
        min-height: 55px;
    }*/
    /*.custom-input{
        height: 22px;
    }*/
    /*.form-group.is-border label{
        font-size: 14px;
    }*/
    .frm-group .form-control {
        min-height: 55px;
    }

    .frm-w-btn .form-control {
        width: calc(100% - 148px);
    }

    .frm-w-btn .btn-normal {
        width: 148px;
    }

    .checkout-footer {
        padding: 40px 0px 0px;
        text-align: right;
    }

    .checkout-footer .checkout-pay-button {
        width: 230px;
        font-size: 18px;
        font-weight: 500;
    }

    .r-precart .item .price {
        font-size: 20px;
        font-weight: 500;
    }

    .meta-price {
        font-size: 18px;
        padding: 0px 0px 20px;
    }

    .meta-price .price {
        font-size: 20px;
    }

    .cart-total {
        padding: 0px 0px 20px;
    }

    .total-price {
        font-size: 18px;
        padding: 37px 0px 0px;
    }

    .total-price .price {
        font-size: 26px;
    }

    .shipping-list {
        margin-left: 30px;
    }

    .label-checkbox {
        padding-left: 42px;
    }

    .checkmark {
        height: 28px;
        width: 28px;
        margin-top: -14px;
    }

    .label-checkbox .checkmark:after {
        top: 6px;
        left: 6px;
        width: 14px;
        height: 14px;
    }

    .delivery-information .form-group textarea.form-control {
        min-height: 118px;
        padding: 15px 15px 15px 15px;
    }

    .frm-group .field-group .form-group {
        margin: 0px 0px 16px;
    }

    .frm-group .form-group {
        margin: 0px 0px 24px;
    }

    .field-group .form-group:first-child, .delivery-information .form-group:first-child {
        padding-right: 12px;
    }

    .delivery-information .form-group.pdl-0:last-child {
        padding-left: 0px;
    }

    .date-time .item:last-child .label-checkbox {
        margin-bottom: 0px;
    }
}

@media (max-width: 767px) {
    .l-precart .ctn-inner {
        padding: 0px 0px 30px;
    }

    .frm-group .field-group {
        padding: 0px 0px 6px;
    }

    .delivery-option {
        padding: 0px 0px 20px;
    }

    .delivery-information {
        padding: 0px 0px 0px;
    }
}
</style>
<template>
    <div>
        <div class="row">
            <div class="col-md-7 col-lg-7 l-precart">
                <div class="ctn-inner">
                    <div class="frm-group">
                        <h1 class="p-title">Order Confirmation</h1>
                        <div class="row">
                            <div class="col-12">
                                <div id="shipping_details_errors" v-if="errors.shipping_details.length > 0"
                                     class="alert alert-danger">
                                    <ul class="list-unstyled mb-0">
                                        <li v-for="message in errors.shipping_details">{{ message }}</li>
                                    </ul>
                                </div>
                                <div id="buyer_details_errors" v-if="errors.buyer_details.length > 0"
                                     class="alert alert-danger mb-4">
                                    <ul class="list-unstyled mb-0">
                                        <li v-for="message in errors.buyer_details">{{ message }}</li>
                                    </ul>
                                </div>
                                <div class="mb-5" v-if="business.seller_notes">
                                    <h6 class="p-title">Seller Notes</h6>
                                    <small class="text-muted text" v-if="business.seller_notes">{{
                                            business.seller_notes
                                        }}</small>
                                </div>
                                <h6 class="p-title">Contact Information</h6>
                                <div class="field-group">
                                    <div class="row">
                                        <div class="col-md-6 form-group">
                                            <input v-model="checkout.email" class="form-control" id="email" type="email"
                                                   :disabled="is_processing" placeholder="Email">
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <input v-model="checkout.phone_number" class="form-control" id="phone"
                                                   type="text"
                                                   :disabled="is_processing" placeholder="Phone">
                                        </div>
                                    </div>
                                </div>
                                <div v-if="business.enabled_shipping || business.can_pick_up" class="shipping-option">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <h6 class="p-title">Select Delivery Option</h6>
                                            <div class="delivery-option">
                                                <div v-if="available_shipping_options.length > 0">
                                                    <label class="label-checkbox">Shipping
                                                        <input type="radio" :value="true" v-model="has_shipping"
                                                               @change="updateTotalAmount(false, 'shipping')" :disabled="is_processing">
                                                        <span class="checkmark"></span>
                                                    </label>
                                                </div>
                                                <div class="shipping-list" v-if="has_shipping">
                                                    <div v-for="shipping in available_shipping_options">
                                                        <label class="label-checkbox">{{ shipping.name }}
                                                            <input type="radio" :value="shipping.id"
                                                                   v-model="checkout.shipping.option"
                                                                   :disabled="is_processing">
                                                            <span class="checkmark"></span>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div v-if="business.can_pick_up">
                                                    <label class="label-checkbox">Self pickup
                                                        <input type="radio" :value="false" v-model="has_shipping"
                                                               @change="updateTotalAmount(true, 'self pickup')"
                                                               :disabled="is_processing">
                                                        <span class="checkmark"></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div v-if="shipping_timeslots.length > 0 && !checkout.customer_pickup"
                                                 class="form-group">
                                                <div class="date-time">
                                                    <h6 class="p-title">Choose Date and Time slot</h6>
                                                    <div class="radio-toolbar">
                                                        <template v-for="slot in shipping_timeslots">
                                                            <div class="item">
                                                                <label :for="slot.id"
                                                                       class="label-checkbox">{{ getSlotName(slot) }}
                                                                    <input type="radio" :id="slot.id" name="slots"
                                                                           v-model="timeslot_index"
                                                                           :value="slot.id">
                                                                    <span class="checkmark"></span>
                                                                </label>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                            <div v-if="checkout.customer_pickup && business.slots != null"
                                                 class="form-group">
                                                <div class="radio-toolbar">
                                                    <template v-for="slot in pickup_slots">
                                                        <div class="item">
                                                            <label :for="slot.id"
                                                                   class="label-checkbox">{{ getSlotName(slot) }}
                                                                <input type="radio" :id="slot.id" name="slots"
                                                                       v-model="pickup_slot_index"
                                                                       :value="slot.id">
                                                                <span class="checkmark"></span>
                                                            </label>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="business.enabled_shipping || checkout.customer_pickup" class="delivery-information">
                            <div class="row">
                                <div class="col-12">
                                    <h6 class="p-title">Delivery Information</h6>
                                    <div class="row">
                                        <div class="form-group col-md-6 col-sm-12">
                                            <input type="text" class="form-control" v-model="checkout.last_name"
                                                   id="last_name"
                                                   :disabled="is_processing"
                                                   placeholder="Name">
                                        </div>
                                        <div class="form-group col-md-6 col-sm-12" v-if="!checkout.customer_pickup">
                                            <input type="text" class="form-control" v-model="checkout.shipping.street"
                                                   id="shipping_street"
                                                   :disabled="is_processing"
                                                   placeholder="Address">
                                        </div>
                                    </div>
                                    <div v-if="checkout.customer_pickup">
                                        <span class="form-text text-muted">Pickup Address: {{business.address_line}}</span>
                                    </div>
                                    <div v-if="!checkout.customer_pickup">
                                        <div class="row">
                                            <div class="form-group col-md-6 col-sm-12">
                                                <input type="text" class="form-control" v-model="checkout.shipping.city"
                                                       id="shipping_city"
                                                       :disabled="is_processing"
                                                       placeholder="City">
                                            </div>
                                            <div class="form-group col-md-6 col-sm-12">
                                                <input type="text" class="form-control"
                                                       v-model="checkout.shipping.state" id="shipping_state"
                                                       :disabled="is_processing"
                                                       placeholder="State">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="form-group col-md-6">
                                                <select v-model="checkout.shipping.country" id="country"
                                                        class="country form-control"
                                                        title="Country"
                                                        :disabled="is_processing">
                                                    <option v-for="country in countries" :value="country.code">
                                                        {{ country.name }}
                                                    </option>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-6">
                                                <input type="text" class="form-control"
                                                       v-model="checkout.shipping.postal_code"
                                                       :disabled="is_processing" placeholder="Postal code"
                                                       id="shipping_postal_code">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group pdl-0">
                                        <textarea name="remark" class="form-control" v-model="checkout.remark"
                                                  title="Remark" rows="2" placeholder="Remarks to seller"
                                                  :disabled="is_processing"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="!business.enabled_shipping && !checkout.customer_pickup" class="pt-1">
                            <div class="row">
                                <div class="form-group col-md-12 col-sm-12">
                                    <input type="text" class="form-control" v-model="checkout.last_name"
                                        id="last_name"
                                        :disabled="is_processing"
                                        placeholder="Name">
                                </div>
                            </div>
                        </div>
                        <div class="frm-w-btn pb-3">
                            <input type="text" class="form-control"
                                   v-model="coupon.code"
                                   :disabled="is_processing"
                                   :class="{'is-invalid': errors.coupon}"
                                   placeholder="Apply coupon code if you have one">
                            <button class="btn btn-normal btn-larger" :style="buttonStyle"
                                    @click="applyCoupon()">Apply
                            </button>
                            <span v-if="errors.coupon != ''" class="invalid-feedback d-block"
                                  role="alert">{{ errors.coupon }}</span>
                        </div>
                    </div>
                </div>
                <div class="checkout-footer d-none d-md-block d-lg-block">
                    <button class="btn btn-normal btn-larger checkout-pay-button" type="button" :style="buttonStyle"
                            @click="payNow()"
                            :disabled="is_processing">Pay
                        {{ displayTotalAmount }}
                        <i class="fas fa-spin fa-spinner" :class="{'d-none' : !is_processing}"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-5 col-lg-5 r-precart">
                <div class="ctn-inner">
                  <div class="pre-cart-detail d-none d-md-block d-lg-block">
                    <div v-for="(variation,index) in variations" :key="index" class="item">
                      <div class="row">
                        <div class="checkout-product">
                          <div class="col-12">
                            <template v-if="variation.model.product.images_count > 0">
                              <img :src="variation.image"
                                   :alt="variation.model.product.name" class="img-precheckout">
                            </template>
                            <template v-else>
                              <img src="/hitpay/images/product.jpg" class="img-precheckout" alt="product">
                            </template>
                            <span class="f-bold">{{ variation.cart.quantity }}</span>
                          </div>
                        </div>
                      </div>
                      <div class="row d-flex align-items-center">
                        <div class="col-7 text-left">
                          <h6 class="p-title">{{ variation.model.product.name }}</h6>
                          <span v-if="variation.model.description"
                                class="variation-name">{{ variation.model.description }}</span>
                        </div>
                        <div class="col-5 text-right">
                          <span class="price" :style="isProductPriceUpdated(variation) ? 'text-decoration: line-through;' : ''">
                            {{ displayProductPrice(variation) }}
                          </span>
                        </div>
                      </div>
                      <div v-if="is_product_price_changes_by_coupon">
                        <div v-for="(couponUpdatedPrice, idx) in coupon_updated_price" :key="idx" class="row d-flex align-items-center">
                          <template v-if="couponUpdatedPrice.product_variation_id === variation.model.id">
                            <div class="col-7 text-left">
                              <h6 class="p-title">Coupon: </h6>
                              <span class="variation-name">{{ couponUpdatedPrice.coupon_name }}</span>
                            </div>
                            <div class="col-5 text-right">
                              <span class="price">{{ getFormattedAmount(business.currency, couponUpdatedPrice.product_price_changed) }}</span>
                            </div>
                          </template>
                        </div>
                      </div>
                    </div>
                  </div>

                    <div class="meta-price">
                        <div class="row pb-2 pt-3 cart-total">
                            <div class="col-5 text-left">
                                <span>Cart:</span>
                            </div>
                            <div class="col-7 text-right">
                                <span class="price">{{ displayTotalCartAmount }}</span>
                            </div>
                        </div>
                        <div v-if="business.enabled_shipping" class="row d-flex align-items-center pb-2">
                            <div class="col-5 text-left">
                                <span>Shipping</span>
                            </div>
                            <div class="col-7 text-right">
                                <span class="price">{{ displayShippingRate }}</span>
                            </div>
                        </div>

                        <div class="row d-flex align-items-center pb-2" v-if="discount.status === 'success'">
                            <div class="col-5">
                                <span>Discount {{ getDiscountNameApplied }}</span>
                            </div>
                            <div class="col-7 text-right">
                                <span class="price">- {{ getDiscountAmountApplied }}</span>
                            </div>
                        </div>

                        <div class="row d-flex align-items-center pb-2" v-if="coupon.amount">
                            <div class="col-5">
                                <span>Coupon discount</span>
                            </div>
                            <div class="col-7 text-right">
                                <span class="price">- {{ displayCouponAmount }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="total-price">
                        <div class="row d-flex align-items-center order-total">
                            <div class="col-5 text-left">
                                <span>Total:</span>
                            </div>
                            <div class="col-7 text-right">
                                <span class="price">{{ displayTotalAmount }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="checkout-footer d-md-none d-lg-none">
                    <div class="row">
                        <div class="col-md-7 col-lg-7">
                            <button class="btn btn-normal checkout-pay-button" type="button" :style="buttonStyle"
                                    @click="payNow()"
                                    :disabled="is_processing">Pay
                                {{ displayTotalAmount }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- <div class="checkout-footer">
            <div class="row">
                <div class="col-md-7 col-lg-7">
                    <div class="container">
                        <div class="d-flex justify-content-end">
                            <button class="btn-normal checkout-pay-button" type="button" :style="buttonStyle"
                                    @click="payNow()"
                                    :disabled="is_processing">Pay
                                ${{ (order.total_amount ? order.total_amount : totalCartAmount).toFixed(2) }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div> -->
    </div>
</template>


<script>
import GetTextColor from '../../mixins/GetTextColor';
import CurrencyNumber from '../../mixins/CurrencyNumber';

export default {
    mixins: [
        GetTextColor,
        CurrencyNumber
    ],
    props: {
        customisation: {
            type: Object,
            required: true
        },
    },
    watch: {
        'checkout.shipping.country': {
            handler(country) {
                if (this.business.enabled_shipping) {
                    this.is_processing = true;
                    this.available_shipping_options = [];

                    let selected_option_id = '';
                    for (const [key, value] of Object.entries(this.shipping_options)) {
                        _.each(value.options, (option) => {
                            if (selected_option_id === '')
                                selected_option_id = option.id;

                            this.available_shipping_options.push(option);
                        });
                    }
                    this.checkout.shipping.option = selected_option_id;

                    this.is_processing = false;
                }
            },
            deep: true,
        },

        'checkout.shipping.option': {
            handler(option) {
                if (this.business.enabled_shipping) {
                    this.is_processing = true;

                    let tempShippingOption = _.find(this.available_shipping_options, [
                        'id',
                        option,
                    ]);

                    if (tempShippingOption) {
                        this.add_to_cart.shipping_calculation = tempShippingOption.calculation;
                        this.add_to_cart.shipping_rate = tempShippingOption.rate;
                        this.add_to_cart.shipping_rate_stored = tempShippingOption.rate_stored;
                    }

                    this.updateTotalAmount(this.checkout.customer_pickup, 'shipping option');

                    if (tempShippingOption.slots != null) {
                        this.setTimeSlots(tempShippingOption.slots);
                    } else {
                        this.shipping_timeslots = [];
                        this.timeslot_index = null;
                    }

                    this.is_processing = false;
                }
            },
            deep: true
        },
    },
    data() {
        return {
            business: {
                can_pick_up: false,
                address_line: '',
            },
            add_to_cart: {
                shipping_calculation: '',
                shipping_rate: 0,
                shipping_rate_stored: 0,
            },
            errors: {
                buyer_details: [],
                shipping_details: [],
                coupon: '',
            },
            variations: [],
            checkout: {
                first_name: '',
                last_name: '',
                email: '',
                phone_number: '',
                customer_pickup: false,
                remark: '',
                shipping: {
                    street: '',
                    state: '',
                    city: '',
                    postal_code: '',
                    country: '',
                    option: '',
                },
            },
            totalCartAmount: 0,
            totalPrice: 0,
            totalCartQuantity: 0,
            available_shipping_options: [],
            shipping_timeslots: [],
            pickup_slots: [],
            timeslot_index: null,
            pickup_slot_index: null,
            shipping_options: [],
            has_shipping: false,
            is_processing: false,
            is_disabled: false,
            countries: {},
            order: {
                total_quantity: 0,
                total_amount: 0,
                total_amount_stored: 0,
                total_amount_calculated: 0,
            },
            discount: {
                name: '',
                amount: 0,
                displayAmount: 0,
            },
            coupon: {
                id: '',
                code: '',
                amount: 0,
                type: '',
            },
            is_product_price_changes_by_coupon: false,
            coupon_updated_price: [],
            shipping_discount: null,
            themeColors: {
                hitpay: {
                    mainColor: '#011B5F',
                    textColor: 'white',
                },
                light: {
                    mainColor: '#797979',
                    textColor: 'white',
                },
                custom: {
                    mainColor: this.customisation.tint_color,
                    textColor: this.getTextColor(this.customisation.tint_color),
                }
            },
            usable_data: {
                week_days_list: {
                    1: 'Monday',
                    2: 'Tuesday',
                    3: 'Wednesday',
                    4: 'Thursday',
                    5: 'Friday',
                    6: 'Saturday',
                    7: 'Sunday',
                },
                monthList: [
                    'January',
                    'February',
                    'March',
                    'April',
                    'May',
                    'June',
                    'July',
                    'August',
                    'September',
                    'October',
                    'November',
                    'December'
                ]
            }
        };
    },
    mounted() {
        this.business = Business;
        this.countries = CheckoutOptions.countries_list;

        if (this.business.enabled_shipping) {
            this.shipping_options = CheckoutOptions.shippings;
            this.has_shipping = Object.keys(this.shipping_options).length > 0;
            this.checkout.customer_pickup = false;
        }else if(!this.business.enabled_shipping && this.business.can_pick_up) {
            this.checkout.customer_pickup = true;
        }

        this.totalCartAmount = TotalCartAmount;
        this.variations = Variations;
        this.discount = Discount;

        let vars = [];
        let i = 0;
        _.each(this.variations, (variation) => {
            this.errors[i] = [];
            vars.push(variation);
            i++;
        });
        this.variations = vars;

        if (this.business.enabled_shipping) {
            this.checkout.shipping.country = this.business.country;

            for (const [key, value] of Object.entries(this.shipping_options)) {
                _.each(value.options, (option) => {
                    this.available_shipping_options.push(option);
                });
            }

            if (ShippingDiscount) {
                this.shipping_discount = ShippingDiscount;
            }
        }

        if (this.business.slots != null && this.business.slots !== '') {
            this.business.slots = JSON.parse(this.business.slots);
        }

        if (this.business.can_pick_up && this.business.slots != null) {
                for (let i = 0; i < 3; i++) {
                    _.each(this.business.slots, slot => {
                        var today = new Date();
                        let dayIndex = Object.keys(this.usable_data.week_days_list).find(key => this.usable_data.week_days_list[key] === slot.day);
                        //get next date from the weekday
                        today.setDate(today.getDate() + (dayIndex - 1 - today.getDay() + 7) % 7 + 1 + 7 * i);
                        let s = {
                            date: today,
                            times: slot.times,
                            id: parseInt(dayIndex) + 7 * i
                        }
                        this.pickup_slots.push(s);
                    });
                }
                this.pickup_slots.sort(function (a, b) {
                    return a.date.getTime() - b.date.getTime()
                });
            }

        this.order.total_quantity = TotalCartQuantity;
        this.order.total_amount_calculated = TotalCartAmount;
        this.order.total_amount = TotalCartAmount;
        this.updateTotalAmount(this.checkout.customer_pickup, 'initial');

    },
    methods: {
      calculateCartTotalAmountByVariant() {
        let totalCartTotalAmount = 0;

        _.each(this.variations, (itemVariation) => {
          if (this.is_product_price_changes_by_coupon) {
            let productPrice = itemVariation.model.price;

            _.each(this.coupon_updated_price, (itemVariationChanges) => {
              if (itemVariationChanges.product_variation_id === itemVariation.model.id) {
                productPrice = itemVariationChanges.product_price_changed;
              }
            });

            totalCartTotalAmount = totalCartTotalAmount + (productPrice * itemVariation.cart.quantity);
          } else {
            totalCartTotalAmount = totalCartTotalAmount + (itemVariation.model.price * itemVariation.cart.quantity);
          }
        });

        this.totalCartAmount = totalCartTotalAmount;
      },
        updateTotalAmount(selfPickUp = false, flag='') {
            if (this.is_product_price_changes_by_coupon) {
              this.calculateCartTotalAmountByVariant();

              if (selfPickUp) {
                this.order.total_amount = this.totalCartAmount - this.getDiscountRealAmount;
              } else {
                let shipping_rate = this.shippingRate * 1;

                this.checkout.customer_pickup = false;

                this.order.total_amount = (this.totalCartAmount + shipping_rate) - this.getDiscountRealAmount;
              }
            } else {
              let couponAmount = this.coupon.type === 'fixed' ? this.coupon.amount : this.totalCartAmount * this.coupon.amount;

              if (selfPickUp) {
                this.checkout.customer_pickup = true;

                this.order.total_amount = this.order.total_amount_calculated - this.getDiscountRealAmount - couponAmount;
              } else {
                let shipping_rate = this.shippingRate * 1;

                this.checkout.customer_pickup = false;

                this.order.total_amount = (this.order.total_amount_calculated + shipping_rate) - this.getDiscountRealAmount - couponAmount;
              }
            }
        },

        payNow() {
            this.is_processing = true;

            this.errors.buyer_details = [];
            this.errors.shipping_details = [];

            document.getElementById('last_name').setAttribute('class', 'form-control');
            document.getElementById('email').setAttribute('class', 'form-control');
            document.getElementById('phone').setAttribute('class', 'form-control');

            if (this.business.enabled_shipping) {
                if (document.getElementById('shipping_street') != null)
                    document.getElementById('shipping_street').setAttribute('class', 'form-control');

                if (document.getElementById('shipping_state') != null)
                    document.getElementById('shipping_state').setAttribute('class', 'form-control');

                if (document.getElementById('shipping_city') != null)
                    document.getElementById('shipping_city').setAttribute('class', 'form-control');

                if (document.getElementById('shipping_postal_code') != null)
                    document.getElementById('shipping_postal_code').setAttribute('class', 'form-control');

                if (this.checkout.last_name === '') {
                    this.errors.buyer_details.push('You must enter your name.');
                    document.getElementById('last_name').setAttribute('class', 'form-control field-required');
                }
            }

            if (!this.business.enabled_shipping) {
                if (this.checkout.last_name === '') {
                    this.errors.buyer_details.push('You must enter your name.');
                    document.getElementById('last_name').setAttribute('class', 'form-control field-required');
                }
            }

            if (this.checkout.email === '') {
                this.errors.buyer_details.push('You must enter your email.');
                document.getElementById('email').setAttribute('class', 'form-control field-required');
            } else if (!this.validateEmail(this.checkout.email)) {
                this.errors.buyer_details.push('The email field must be a valid email address.');
                document.getElementById('email').setAttribute('class', 'form-control field-required');
            } else if (this.checkout.email.length > 255) {
                this.errors.buyer_details.push('The email may not be greater than 255 characters.');
                document.getElementById('email').setAttribute('class', 'form-control field-required');
            }

            if (this.checkout.phone_number === '') {
                this.errors.buyer_details.push('You must enter your phone number.');
                document.getElementById('phone').setAttribute('class', 'form-control field-required');
            } else if (this.checkout.phone_number.length > 14) {
                this.errors.buyer_details.push('The phone number may not be greater than 14 characters.');
                document.getElementById('phone').setAttribute('class', 'form-control field-required');
            } else if (isNaN(this.checkout.phone_number)) {
                this.errors.buyer_details.push('The phone number must be a number.');
                document.getElementById('phone').setAttribute('class', 'form-control field-required');
            }

            if (this.business.enabled_shipping) {
                if (this.checkout.customer_pickup === false) {
                    if (this.checkout.shipping.street === '') {
                        this.errors.shipping_details.push('The shipping street can\'t be empty.');
                        document.getElementById('shipping_street').setAttribute('class', 'form-control field-required');
                    }

                    if (this.checkout.shipping.state === '') {
                        this.errors.shipping_details.push('The shipping state can\'t be empty.');
                        document.getElementById('shipping_state').setAttribute('class', 'form-control field-required');
                    }

                    if (this.checkout.shipping.city === '') {
                        this.errors.shipping_details.push('The shipping city can\'t be empty.');
                        document.getElementById('shipping_city').setAttribute('class', 'form-control field-required');
                    }

                    if (this.checkout.shipping.postal_code === '') {
                        this.errors.shipping_details.push('The shipping postal_code can\'t be empty.');
                        document.getElementById('shipping_postal_code').setAttribute('class', 'form-control field-required');
                    }

                    if (this.checkout.shipping.country === '') {
                        this.errors.shipping_details.push('The shipping country can\'t be empty.');
                        document.getElementById('shipping_country ').setAttribute('class', 'form-control field-required');
                    }

                    if (this.checkout.shipping.option === '') {
                        this.errors.shipping_details.push('The shipping street can\'t be empty.');
                    }

                    if (this.shipping_timeslots.length > 0 && this.timeslot_index === null) {
                        this.errors.shipping_details.push('Please choose date slot for your delivery');
                    }
                } else if (this.checkout.customer_pickup && this.pickup_slot_index === null && this.business.slots.length > 0) {
                    this.errors.shipping_details.push('Please choose date slot for your pickup');
                }
            }

            if (this.errors.buyer_details.length > 0) {
                this.scrollTo('#buyer_details_errors');
                this.is_processing = false;

                return;
            }

            let shippingSlot = [];

            if (this.business.enabled_shipping) {
                if (this.errors.shipping_details.length > 0) {
                    this.scrollTo('#shipping_details_errors');
                    this.is_processing = false;

                    return;
                }

                let slot = null

                if (this.timeslot_index != null && !this.checkout.customer_pickup) {
                    slot = this.shipping_timeslots.find(x => x.id === this.timeslot_index);
                    shippingSlot = {
                        date: this.datefilter(slot.date),
                        times: {
                            from: slot.times.from,
                            to: slot.times.to,
                        }
                    }
                } else if (this.checkout.customer_pickup && this.pickup_slot_index != null) {
                    slot = this.pickup_slots.find(x => x.id === this.pickup_slot_index);
                    shippingSlot = {
                        date: this.datefilter(slot.date),
                        times: {
                            from: slot.times.from,
                            to: slot.times.to,
                        }
                    }
                } else shippingSlot = null;
            }

            let couponAmount = 0;

            if (!this.is_product_price_changes_by_coupon) {
              couponAmount = this.coupon.type === 'fixed' ? this.coupon.amount : this.totalCartAmount * this.coupon.amount;
            }

            let submissionData = {
                first_name: this.checkout.first_name,
                last_name: this.checkout.last_name,
                email: this.checkout.email,
                phone_number: this.checkout.phone_number,
                remark: this.checkout.remark,
                discount: this.discount,
                coupon:{
                  amount: couponAmount,
                  id: this.coupon.id
                }
            };

            if (this.discount.status === 'success') {
              submissionData.discount = {
                name: this.getDiscountNameApplied,
                amount: this.getDiscountRealAmount,
                data: this.getDiscountData,
              };
            } else {
              submissionData.discount = {
                name: '',
                amount: 0,
                data: null,
              };
            }

            if (this.business.enabled_shipping) {
                submissionData.customer_pickup = this.checkout.customer_pickup;
                submissionData.date_slot = shippingSlot;
                submissionData.shipping = {
                    address: {
                        street: this.checkout.shipping.street,
                        city: this.checkout.shipping.city,
                        postal_code: this.checkout.shipping.postal_code,
                        state: this.checkout.shipping.state,
                        country: this.checkout.shipping.country.toLowerCase(),
                    },
                    option: this.checkout.shipping.option,
                };
                submissionData.shipping_rate = this.shippingRate;
            }

            umami('click_pay');

            return axios.post(this.getDomain(this.business.id + '/checkout', 'shop'), submissionData).then(({data}) => {
                this.is_processing = false;

                window.location = data;
            }).catch(({response}) => {
                this.is_processing = false;

                if (response.status === 422) {
                    _.forEach(response.data.errors, (value, key) => {
                        if (key === 'email') {
                            this.errors.buyer_details.push(_.first(value));
                            document.getElementById('email').setAttribute('class', 'form-control field-required');
                        }

                        if (key === 'phone_number') {
                            this.errors.buyer_details.push(_.first(value));
                            document.getElementById('phone_number').setAttribute('class', 'form-control field-required');
                        }
                    });
                }
            });
        },

        applyCoupon() {
            this.is_processing = true;

            this.errors.coupon = '';

            if (this.coupon.code === '') {
                this.errors.coupon = 'Please enter your coupon code';
                this.coupon.amount = 0;
                this.coupon.type = '';
                this.updateTotalAmount(this.checkout.customer_pickup, 'applycoupon 1');
                this.is_processing = false;
            } else {
                return axios.get(this.getDomain(this.business.id + '/getJsonCoupon/' + this.coupon.code, 'shop'))
                  .then(({data}) => {
                    if (Object.keys(data).length <= 0) {
                      this.coupon.amount = 0;
                      this.coupon.type = '';
                      this.updateTotalAmount(this.checkout.customer_pickup, 'applycoupon 3');
                      this.errors.coupon = 'There is no coupon with such code.';
                      this.is_processing = false;
                      return;
                    }

                    if (data.status === 'failed') {
                      this.coupon.amount = 0;
                      this.coupon.type = '';
                      this.updateTotalAmount(this.checkout.customer_pickup, 'applycoupon 3');
                      this.errors.coupon = data.message;
                      this.is_processing = false;
                      return;
                    }

                    if (data.coupon.coupons_left && data.coupon.coupons_left <= 0) {
                      this.errors.coupon = 'This coupon is not available anymore.';
                      this.is_processing = false;
                      return;
                    }

                    this.is_product_price_changes_by_coupon = data.is_product_price_changed;

                    if (!data.is_product_price_changed) {
                      this.coupon.amount = data.fixed_amount ? data.fixed_amount : data.percentage;
                      this.coupon.type = data.fixed_amount ? 'fixed' : 'percent';
                      this.coupon.id = data.id;
                      this.is_processing = false;

                      this.updateTotalAmount(this.checkout.customer_pickup, 'applycoupon 2');
                      return;
                    }

                    if (data.is_product_price_changed) {
                      this.coupon_updated_price = data.coupon_information;
                      this.coupon.id = data.coupon.id;
                      this.coupon.amount = 0;
                      this.is_processing = false;

                      this.updateTotalAmount(this.checkout.customer_pickup, 'is product price changed 3');
                    }
                });
            }
        },

        setTimeSlots(timeSlots) {
            this.shipping_timeslots = [];
            for (let i = 0; i < 3; i++) {
                _.each(timeSlots, slot => {
                    var today = new Date();
                    let dayIndex = Object.keys(this.usable_data.week_days_list).find(key => this.usable_data.week_days_list[key] === slot.day);
                    //get next date from the weekday
                    today.setDate(today.getDate() + (dayIndex - 1 - today.getDay() + 7) % 7 + 1 + 7 * i);
                    let s = {
                        date: today,
                        times: slot.times,
                        id: parseInt(dayIndex) + 7 * i
                    }
                    this.shipping_timeslots.push(s);
                });
            }
            this.shipping_timeslots.sort(function (a, b) {
                return a.date.getTime() - b.date.getTime()
            });
        },

        getSlotName(slot) {
            return slot.date.getDate() + ' ' + this.usable_data.monthList[slot.date.getMonth()] + ' ' + slot.times.from + '-' + slot.times.to;
        },

        datefilter(date) {
            let month = date.getMonth() + 1;
            let year = date.getFullYear();
            let day = date.getDate();

            if (day < 10) {
                day = '0' + day;
            }

            if (month < 10) {
                month = '0' + month;
            }
            return year + '-' + month + '-' + day;
        },

        displayProductPrice(variation) {
          return this.getFormattedAmount(this.business.currency, variation.model.price);
        },

        isProductPriceUpdated(variation) {
          if (!this.is_product_price_changes_by_coupon) {
            return false;
          }

          let isProductPriceUpdated = false;

          if (this.is_product_price_changes_by_coupon) {
            _.each(this.coupon_updated_price, (itemVariationChanges) => {
              if (itemVariationChanges.product_variation_id === variation.model.id) {
                isProductPriceUpdated = true;
              }
            });
          }

          return isProductPriceUpdated;
        }
    }
    ,
    computed: {
        currentThemeColors() {
            return this.themeColors[this.safeTheme]
        }
        ,
        buttonStyle() {
            return {
                color: this.currentThemeColors.textColor,
                'background-color': this.currentThemeColors.mainColor,
            }
        }
        ,
        safeTheme() {
            let theme = this.customisation.theme

            // fail-safe for incorrect theme name
            if (!this.themeColors[theme]) {
                theme = 'hitpay'
            }

            return theme
        }
        ,
        shippingRate() {
            if (this.checkout.customer_pickup) {
                return 0.00;
            }

            let shipping_price;

            if (this.add_to_cart.shipping_calculation === 'fee_per_unit') {
                shipping_price = this.add_to_cart.shipping_rate_stored * this.order.total_quantity;
            }else{
                shipping_price = this.add_to_cart.shipping_rate_stored;
            }

            if (this.shipping_discount != null) {
                if (this.order.total_amount_calculated > this.shipping_discount.minimum_cart_amount) {
                    if (this.shipping_discount.type === 'free')
                        shipping_price = 0.00;
                    else if (this.shipping_discount.type === 'fixed'){
                        if(this.shipping_discount.fixed_amount > shipping_price){
                            shipping_price = 0.00
                        }else {
                            shipping_price -= this.shipping_discount.fixed_amount;
                        }
                    }
                    else if (this.shipping_discount.type === 'percent') {
                        shipping_price -= shipping_price * this.shipping_discount.percentage;
                    }
                }
            }

            return shipping_price;
        },

        displayCouponAmount() {
            if (this.coupon.type === 'fixed') {
                return this.getFormattedAmount(this.business.currency, this.coupon.amount);
            } else {
                return this.getFormattedAmount(this.business.currency, (this.totalCartAmount * this.coupon.amount));
            }
        },

        displayTotalAmount() {
            return this.getFormattedAmount(this.business.currency, this.order.total_amount);
        },

        displayTotalCartAmount() {
            return this.getFormattedAmount(this.business.currency, this.totalCartAmount);
        },

        displayShippingRate() {
            if (this.checkout.customer_pickup) {
                return '0';
            } else {
                return this.getFormattedAmount(this.business.currency, this.shippingRate);
            }
        },

        getDiscountNameApplied() {
          if (this.discount.status !== 'success') {
            return '';
          }

          let name = '';

          _.each(this.discount.discount_information, discount => {
            if (name === '') {
              name += ' ' + discount.discount_applied.name;
            } else {
              name += ' & ' + discount.discount_applied.name;
            }
          });

          name = name.trim();

          return '[' + name + ']';
        },
        getDiscountAmountApplied() {
          if (this.discount.status !== 'success') {
            return this.getFormattedAmount(this.business.currency, 0);
          }

          let discountAmount = this.getDiscountRealAmount;

          return this.getFormattedAmount(this.business.currency, discountAmount);
        },

        getDiscountRealAmount() {
          let discountAmount = 0;

          _.each(this.discount.discount_information, discount => {
            discountAmount = discountAmount + parseInt(discount.discount_amount);
          });

          return discountAmount;
        },

        getDiscountData() {
          let discountInfoCollection = [];

          _.each(this.discount.discount_information, discount => {
            let discountData = {}
            discountData.id = discount.discount_applied.id;
            discountData.data = discount;

            discountInfoCollection.push(discountData);
          });

          return discountInfoCollection;
        }
    }
}
</script>
