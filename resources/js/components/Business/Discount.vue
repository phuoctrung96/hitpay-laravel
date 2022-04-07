<template>
    <div>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h2 class="text-primary mb-0 title">Automatic Store Discounts</h2>
                <p>Customers will get a discount automatically in their cart</p>
            </div>
            <div class="card-body border-top">
                <form id="business-discount" ref="businessDiscount">
                    <div class="form-group">
                        <label for="minimum_cart_amount">Minimum Purchase Amount (Applies to entire order)<span class="text-danger">*</span></label>
                        <input id="minimum_cart_amount" type="number" @keypress="isNumber($event)" placeholder="10.00" step="0.01" min="0" v-model="form.minimum_cart_amount" :class="{'is-invalid' : errors.minimum_cart_amount}" class="form-control bg-light" title="Minimum cart amount">
                        <span class="invalid-feedback" role="alert" v-if="errors.minimum_cart_amount">{{ errors.minimum_cart_amount }}</span>
                    </div>
                    <div class="form-group">
                        <label for="name">Automatic Discount Name<span class="text-danger">*</span></label>
                        <input id="name" type="text" v-model="form.name" :class="{'is-invalid' : errors.name}" class="form-control bg-light" title="Discount Name" placeholder="discount name" @keyup="changeBannerText">
                        <span class="invalid-feedback" role="alert" v-if="errors.name">{{ errors.name }}</span>
                    </div>
                    <div class="form-group">

                        <div class="form-check">
                            <input class="form-check-input" v-model="form.type" type="radio" name="discount_type" id="percent_discount" value="percent" checked required @change="changeBannerText">
                            <label class="form-check-label" for="percent_discount">
                                Percentage(%)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input"  v-model="form.type" required  type="radio" name="discount_type" id="fixed_discount" value="fixed" @change="changeBannerText">
                            <label class="form-check-label" for="fixed_discount" >
                                Fixed amount
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="value">Discount Value <span class="text-danger">*</span></label>
                        <input id="value" required type="number"  :placeholder="(form.type =='percent'?'10': '10.00')" :step="form.type =='percent'?1: 0.01" v-model="form.value" :class="{
                            'is-invalid' : errors.value,
                        }" class="form-control bg-light" min="0" title="Discount amount" @keypress="isNumber($event)" @keyup="changeBannerText">
                        <span class="invalid-feedback" role="alert" v-if="errors.value">{{ errors.value }}</span>
                    </div>
                    <div class="form-group">
                        <label for="agree"><input v-model="form.is_promo_banner" id="is_promo_banner" type="checkbox" value="agree" class="form-check-label"/> Show as Promo Banner</label>
                        <input id="banner_text" class="form-control bg-light" type="text" v-model="form.banner_text" title="Promo Banner" placeholder="Enter text to display" v-if="form.is_promo_banner">
                    </div>
                </form>
                <button id="createBtn" class="btn btn-success btn-lg btn-block mb-3 shadow-sm" @click="createDiscount()" :disabled="is_busy">
                   {{'Save Changes'}}
                    <i class="fas fa-spin fa-spinner" :class="{
                        'd-none' : !is_busy
                    }"></i></button>
            </div>
        </div>
    </div>
</template>

<script>
    import isNumber from '../../mixins/NumberValidationMixin';
    export default {
        name: "DiscountCreateEdit",
        mixins: [isNumber],
        data: () => {
            return {
                is_loading: false,
                is_busy: false,
                form: {
                    id: null,
                    name: '',
                    minimum_cart_amount: null,
                    type: 'percent',
                    fixed_amount: null,
                    value: null,
                    percentage: 0,
                    is_promo_banner: false,
                    banner_text: 'Use coupon for Off'
                },
                errors: {}
            }
        },
        watch: {
            form: {
                handler(values) {
                    if (this.is_busy) {
                        return;
                    }

                    this.errors.value = null;
                    if (values.minimum_cart_amount !== null  && values.minimum_cart_amount !== '') {
                        let indexOfPeriodForPrice = values.minimum_cart_amount.toString().indexOf('.');
                        let decimalsLengthForPrice = values.minimum_cart_amount.toString().substr(indexOfPeriodForPrice);

                        if (decimalsLengthForPrice && decimalsLengthForPrice.length > 3) {
                            this.errors.minimum_cart_amount = 'The minimum cart amount can\'t have more than two decimals.';
                        }
                    }
                    if (values.value !== null  && values.value !== '' && values.type != 'percent') {
                        let indexOfPeriodForPrice = values.value.toString().indexOf('.');
                        let decimalsLengthForPrice = values.value.substr(indexOfPeriodForPrice);

                        if (decimalsLengthForPrice && decimalsLengthForPrice.length > 3) {
                            this.errors.value = 'The fixed amount can\'t have more than two decimals.';
                        }

                        if (Number.parseInt(values.value.toString()) < 0) {
                            this.errors.value = 'The fixed amount can\'t have less than 0.';
                        }
                    }

                    if (values.value !== null  && values.value !== '' && values.type == 'percent') {
                        let value = values.value.toString();
                        if (Number.parseInt(value) > 100) {
                            this.errors.value = 'The percentage can\'t have more than 100%.';
                        }

                        if (Number.parseInt(value) < 0) {
                            this.errors.value = 'The percentage can\'t have less than 0.';
                        }
                    }
                },
                deep: true
            }
        },
        mounted() {
            if (window.Discount !== undefined)
            {
                this.form.id = Discount.id
                this.form.name = Discount.name;
                this.form.minimum_cart_amount = Number(Discount.minimum_cart_amount/100).toFixed(2);
                this.form.fixed_amount = Number( Discount.fixed_amount/100).toFixed(2);
                this.form.percentage = Discount.percentage;
                this.form.type = Discount.percentage?'percent': 'fixed';
                this.form.value = Discount.percentage? (Discount.percentage * 100).toFixed(2): this.form.fixed_amount;
                this.form.is_promo_banner = Discount.is_promo_banner;
                this.form.banner_text = Discount.banner_text;
            }
        },
        methods: {
            createDiscount(){
                this.errors = {};
                this.is_busy = true;
                
                if ( this.form.minimum_cart_amount == 0 || this.form.minimum_cart_amount === null ){
                    this.errors.minimum_cart_amount = 'Minimum cart amount is required'
                } else if (this.form.name === '' ){
                    this.errors.name = 'Discount name is required'
                } else if (this.form.name.length > 255){
                    this.errors.name = 'Discount name may not be greater than 255 characters'
                } else if (this.form.value === 0)
                {
                    this.errors.value = 'Discount value is required'
                }
                if (this.form.minimum_cart_amount !== null  && this.form.minimum_cart_amount !== '') {
                    let indexOfPeriodForPrice = this.form.minimum_cart_amount.toString().indexOf('.');
                    let decimalsLengthForPrice = this.form.minimum_cart_amount.toString().substr(indexOfPeriodForPrice);

                    if (decimalsLengthForPrice && decimalsLengthForPrice.length > 3) {
                        this.errors.minimum_cart_amount = 'The minimum cart amount can\'t have more than two decimals.';
                    }
                }
                if (this.form.value !== null  && this.form.value !== '' && this.form.type != 'percent') {
                    let indexOfPeriodForPrice = this.form.value.toString().indexOf('.');
                    let decimalsLengthForPrice = this.form.value.substr(indexOfPeriodForPrice);

                    if (decimalsLengthForPrice && decimalsLengthForPrice.length > 3) {
                        this.errors.value = 'The fixed amount can\'t have more than two decimals.';
                    }

                    if (Number.parseInt(this.form.value) < 0) {
                        this.errors.value = 'The fixed amount can\'t have less than 0.';
                    }
                }

                if (this.form.value!== null  && this.form.value !== '' && this.form.type == 'percent') {
                    let value = this.form.value.toString();
                    if (Number.parseInt(value) > 100) {
                        this.errors.value = 'The percentage can\'t have more than 100%.';
                    }

                    if (Number.parseInt(value) < 0) {
                        this.errors.value = 'The percentage can\'t have less than 0.';
                    }
                }

                if (Object.keys(this.errors).length > 0) {
                    this.showError(_.first(Object.keys(this.errors)));
                }
                else {
                    if (this.form.type === 'percent')
                    {
                        this.form.percentage = (this.form.value/100).toFixed(4);
                        this.form.fixed_amount = 0;
                    }
                    else {
                        this.form.fixed_amount = this.form.value;
                        this.form.percentage = 0;
                    }
                    delete  this.form.value;
                    delete  this.form.type;
                    axios.post(this.getDomain('business/' + Business.id + '/discount', 'dashboard'), this.form).then(({data}) => {
                        window.location.href = data.redirect_url;
                    }).catch(({response}) => {
                        if (response.status === 422) {
                            _.forEach(response.data.errors, (value, key) => {
                                this.errors[key] = _.first(value);
                            });
                            this.showError(_.first(Object.keys(this.errors)));
                        }
                    });
                }
            },
            showError(firstErrorKey) {
                if (firstErrorKey !== undefined) {
                    this.scrollTo('#' + firstErrorKey);

                    $('#' + firstErrorKey).focus();
                }
                this.is_busy = false;
            },
            changeBannerText(){
                let value = this.form.value??0;
                let miniCartAmount = this.form.minimum_cart_amount??0;

                if(this.form.type == 'percent') {
                    this.form.banner_text = 'Get ' + parseInt(value) + '% off with minimum purchase of $' + miniCartAmount;
                }else{
                    this.form.banner_text = 'Get $' + parseInt(value) + ' off with minimum purchase of $' + miniCartAmount;
                }
            }
        }
    }
</script>
