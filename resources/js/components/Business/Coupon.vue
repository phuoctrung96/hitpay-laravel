<template>
    <div>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h2 class="text-primary mb-0 title">Store Coupons</h2>
                <p>Customers will get a discount if they apply coupon on checkout page</p>
            </div>
            <div class="card-body border-top">
                <form id="business-discount" ref="businessCoupon">
                    <div class="form-group">
                        <label for="name">Coupon Name<span class="text-danger">*</span></label>
                        <input id="name" type="text" v-model="form.name" :class="{'is-invalid' : errors.name}" class="form-control bg-light" title="Coupon Name" placeholder="coupon name" maxlength="64">
                        <span class="invalid-feedback" role="alert" v-if="errors.name">{{ errors.name }}</span>
                    </div>
                    <div class="form-group">
                        <label for="name">Code<span class="text-danger">*</span></label>
                        <input id="code" type="text" v-model="form.code" :class="{'is-invalid' : errors.code}" class="form-control bg-light" title="Coupon Code" placeholder="coupon code" @keyup="changeBannerText" maxlength="6">
                        <span class="invalid-feedback" role="alert" v-if="errors.code">{{ errors.code }}</span>
                    </div>
                    <div class="form-group">

                        <div class="form-check">
                            <input class="form-check-input" v-model="form.type" type="radio" name="coupon_type" id="percent_discount" value="percent" checked required @change="changeBannerText">
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
                        <label for="value">Coupon Value <span class="text-danger">*</span></label>
                        <input id="value" required type="number"  :placeholder="(form.type =='percent'?'10': '10.00')" :step="form.type =='percent'?1: 0.01" v-model="form.value" :class="{
                            'is-invalid' : errors.value,
                        }" class="form-control bg-light" min="0" title="Discount amount" @keyup="changeBannerText">
                        <span class="invalid-feedback" role="alert" v-if="errors.value">{{ errors.value }}</span>
                    </div>
                    <div class="form-group">
                        <label for="agree"><input v-model="form.is_promo_banner" id="is_promo_banner" type="checkbox" value="agree" class="form-check-label"/> Show as Promo Banner</label>
                        <input id="banner_text" class="form-control bg-light" type="text" v-model="form.banner_text" title="Promo Banner" placeholder="Enter text to display" v-if="form.is_promo_banner">
                    </div>
                </form>
                <button id="createBtn" class="btn btn-success btn-lg btn-block mb-3 shadow-sm" @click="createCoupon()" :disabled="is_busy">
                   {{'Save Changes'}}
                    <i class="fas fa-spin fa-spinner" :class="{
                        'd-none' : !is_busy
                    }"></i></button>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: "Coupon",
        data: () => {
            return {
                is_loading: false,
                is_busy: false,
                form: {
                    id: null,
                    name: '',
                    code: '',
                    type: 'percent',
                    fixed_amount: 0,
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
                        if (Number.parseInt(value) < 0) {
                            this.errors.value = 'The percentage can\'t have less than 0.';
                        }

                        if (Number.parseInt(value) > 100) {
                            this.errors.value = 'The percentage can\'t have more than 100%.';
                        }
                    }
                },
                deep: true
            }
        },
        mounted() {
            if (window.Coupon !== undefined)
            {
                this.form.id = Coupon.id
                this.form.name = Coupon.name;
                this.form.code = Coupon.code;
                this.form.fixed_amount = Number( Coupon.fixed_amount/100).toFixed(2);
                this.form.percentage = Coupon.percentage;
                this.form.type = Coupon.percentage?'percent': 'fixed';
                this.form.value = Coupon.percentage? (Coupon.percentage * 100).toFixed(2): this.form.fixed_amount;
                this.form.is_promo_banner = Coupon.is_promo_banner;
                this.form.banner_text = Coupon.banner_text;
            }
        },
        methods: {
            createCoupon(){
                this.errors = {};
                this.is_busy = true;
                if (this.form.name === '' )
                {
                    this.errors.name = 'Coupon name is required'
                }
                else if (this.form.code === '')
                {
                    this.errors.code = 'Coupon code is required'
                }
                else if (this.form.value == 0 || this.form.value === null)
                {
                    this.errors.value = 'Coupon value is required'
                }
                if (this.form.value !== null  && this.form.value !== '' && this.form.type != 'percent') {
                    let indexOfPeriodForPrice = this.form.value.toString().indexOf('.');
                    let decimalsLengthForPrice = this.form.value.substr(indexOfPeriodForPrice);
                    console.log(indexOfPeriodForPrice);
                     console.log(decimalsLengthForPrice);

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
                        this.form.percentage = 0;
                        this.form.fixed_amount = this.form.value;
                    }
                    delete  this.form.value;
                    delete  this.form.type;
                    axios.post(this.getDomain('business/' + Business.id + '/coupon', 'dashboard'), this.form).then(({data}) => {
                        if (data.coupon_exists) {
                            if (data.name) {
                                $('#name').focus();
                                this.errors.name = 'Coupon name already exists';
                            }
                            if (data.code) {
                                $('#code').focus();
                                this.errors.code = 'Coupon code already exists';
                            }
                            this.is_busy = false;
                        } else {
                            window.location.href = data.redirect_url;
                        }
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
                if(this.form.type == 'percent') {
                    this.form.banner_text = 'Use coupon '+this.form.code+' for '+value+'% Off';
                }else{
                    this.form.banner_text = 'Use coupon '+this.form.code+' for '+parseInt(value)+' Off';
                }
            }
        }
    }
</script>
