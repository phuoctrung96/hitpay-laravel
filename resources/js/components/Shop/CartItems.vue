<style scoped>

.cart-delete-item{
    margin:  0px -15px 20px;
    cursor: pointer;
    float: right;
}

.cart-delete-item img{
    width: 16px;
    height: auto;
}

.l-cart-item{
    padding-left: 0px;
}

.r-cart-item{
    text-align: right;
    float: right;
    padding-right: 0px;
}

.r-cart-item .cart-number{
    float: right;
}

.r-cart-item .price{
    text-align: right;
}

.cart-number{
    width: 126px;
}

.cart-number span{
    width: 38px;
    height: 44px;
    border:  1px solid #D4D6DD;
    text-align: center;
    display: block;
    float: left;
    cursor: pointer;
    padding: 8px 0px 0px;
}

.cart-number input{
    width: 50px;
    height: 44px;
    display: block;
    float: left;
    text-align: center;
    border:  1px solid #D4D6DD;
    border-left: none;
    border-right: none;
    background: #FFF;
    border-radius: 0;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
}

.cart-number input:focus{
    outline: none;
}

.cart-list-items .item{
    margin:  0px 0px 15px;
    padding: 0px 0px 15px;
    border-bottom: 1px solid #EBEBED;
}

.cart-list-items .cart-image img{
    min-width: 63px;
    max-width: 109px;
    width: 100%;
    height: auto;
}

.cart-list-items .cart-product-name{
    font-family: 'Heebo', sans-serif;
    font-weight: 500;
    color: #1E1E1F;
    font-size: 18px;
}

.cart-list-items .cart-variation-name{
    color: #6D6E73;
    font-size: 16px;
}

.total-price{
    margin: 0px 0px 0px;
}

.discount{
    margin: 0px 0px 26px;
}

.total-price .title{
    font-size: 18px;
    font-weight: 500;
}

.total-price .number{
    float: right;
    font-family: 'Heebo', sans-serif;
    font-weight: 500;
    font-size: 24px;
}

.cart-bottom .btn-checkout .btn-normal{
    max-width: 205px;
    float: right;
}

@media (max-width: 767px) {
    .cart-delete-item img{
        width: 14px;
    }

    .r-cart-item{
        max-width: 92px;
    }

    .r-cart-item .cart-number{
        float: right;
    }

    .cart-number{
        width: 92px;
    }

    .cart-number span{
        width: 28px;
        height: 32px;
        padding: 1px 0px 0px;
    }

    .cart-number input{
        height: 32px;
        width: 36px;
    }

    .cart-list-items .cart-product-name{
        font-size: 15px;
    }

    .cart-list-items .cart-variation-name{
        font-size: 13px;
    }

    .total-price{
        margin:  0px 0px 20px;
    }

    .total-price .title{
        font-size: 15px;
    }

    .total-price .number{
        font-size: 20px;
    }

    .cart-bottom .btn-checkout .btn-normal{
        max-width: 100%;
    }
}

@media (max-width: 575px) {
    .l-cart-item .col-3{
        width: 93px;
        max-width: 100%;
        flex: none;
    }

    .l-cart-item .col-9{
        flex: none;
        width: 93px;
        width: calc(100% - 108px);
        padding: 0;
    }
}

@media (min-width: 1200px) {
    .l-cart-item{
        width: 745px;
        flex: 0 0 745px;
        max-width: 745px;
    }
    .l-cart-item .col-3 img{
        max-width: 109px;
    }
    .r-cart-item{
        width: 450px;
        flex: 0 0 450px;
        max-width: 450px;
    }
    .l-cart-item .col-3{
        width: 139px;
        flex: 0 0 139px;
        max-width: 139px;
    }
    .l-cart-item .col-9{
        width: 621px;
        flex: 0 0 621px;
        max-width: 621px;
    }
}

</style>
<template>
    <div>
        <div class="cart-list-items">
            <div class="all-items">
                <div v-for="(variation,key) in variations" class="row align-items-center item clearfix">
                    <div class="col-12">
                        <div class="cart-delete-item">
                            <span @click="deleteProdFromCart(variation.cart.variation_id)" :disabled="is_processing"><img src="/images/delete_icon.svg" alt="delete">
                            </span>
                        </div>
                    </div>
                    <div class="l-cart-item col col-lg-7">
                        <div class="row align-items-center">
                            <div class="col-3 col-lg-3">
                                <div class="cart-image">
                                    <a :href="productLink(variation.model.product.id)">
                                        <template v-if="variation.model.product.images_count > 0">
                                            <img :src="variation.image"
                                                :alt="variation.model.product.name">
                                        </template>
                                        <template v-else>
                                            <img src="/hitpay/images/product.jpg" alt="product">
                                        </template>
                                    </a>
                                </div>
                            </div>
                            <div class="col-9 col-lg-9">
                                <div class="row">
                                    <div class="col-12 col-lg-7">
                                        <div class="cart-product-name">
                                            <a id="productName" :href="productLink(variation.model.product.id)"
                                           class="d-block w-100">{{ variation.model.product.name }}</a>
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-5">
                                        <div class="cart-variation-name">
                                            <span>{{ variation.model.description }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="r-cart-item col col-lg-5">
                        <div class="row align-items-center">
                            <div class="col-md-5 col-lg-5 order-lg-2">
                                <div class="price">
                                    {{ getFormattedAmount(business.currency, variation.model.price) }}
                                </div>
                            </div>
                            <div class="col-md-7 col-lg-7 order-lg-1">
                                <div class="cart-number">
                                    <span class="btn-decrease" @click="decreaseQuantity(variation.cart.variation_id, variation.cart.quantity, key)">-</span>
                                    <input type="text" class="number" min="1" v-model="variation.cart.quantity"
                                           oninput="this.value = Math.abs(this.value)"
                                           @input="updateCart(variation.cart.variation_id, variation.cart.quantity, key)"
                                            :class="{'is-invalid' : errors[key].quantity}">
                                    <span class="btn-increase" @click="increaseQuantity(variation.cart.variation_id, variation.cart.quantity, key)">+</span>

                                    <span v-if="errors[key].quantity" class="d-block small text-danger mt-1" role="alert">
                                        {{ errors[key].quantity }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- <table class="table table-responsive-sm">
            <tbody>
            <tr v-for="(variation,key) in variations">
                <td>
                    <a :href="productLink(variation.model.product.id)">
                            <template v-if="variation.model.product.images_count > 0">
                                <img :src="variation.image"
                                     class="cart-img-left"
                                     :alt="variation.model.product.name">
                            </template>
                            <template v-else>
                                <img src="/hitpay/images/product.jpg"
                                     class="cart-img-left"
                                     alt="product">
                            </template>
                    </a>
                </td>
                <td>
                    <a id="productName" :href="productLink(variation.model.product.id)"
                       class="d-block w-100">{{ variation.model.product.name }}</a>
                </td>
                <td>
                    <small class="text-muted">{{ variation.model.description }}</small>
                </td>
                <td>
                    <button class="btn-decrease" @click="decreaseQuantity(variation.cart.variation_id, variation.cart.quantity, key)">-</button>
                    <input type="text" class="w-50" min="1" v-model="variation.cart.quantity"
                           oninput="this.value = Math.abs(this.value)"
                           @input="updateCart(variation.cart.variation_id, variation.cart.quantity, key)"
                            :class="{'is-invalid' : errors[key].quantity}">
                    <button class="btn-increase" @click="increaseQuantity(variation.cart.variation_id, variation.cart.quantity, key)">+</button>

                    <span v-if="errors[key].quantity" class="d-block small text-danger mt-1" role="alert">
                        {{ errors[key].quantity }}
                    </span>
                </td>
                <td>
                    <button type="button" class="btn btn-outline-danger btn-sm py-0"
                            @click="deleteProdFromCart(variation.cart.variation_id)" :disabled="is_processing">X
                    </button>
                    ${{ variation.model.price }}
                </td>
            </tr>
            </tbody>
        </table> -->

        <div class="cart-bottom clearfix">
            <div class="total-price clearfix">
                <span class="title">Total:  </span> <span class="number">{{ displayTotalAmount }}</span><i
                v-if="is_processing" class="fas fa-spin fa-spinner ml-2"></i>
            </div>
            <div class="discount clearfix" v-if="discount.status === 'success'">
                <span class="text-muted float-right mt-2">
                  {{ getDiscountNameApplied }} discount was applied. (- {{ getDiscountAmountApplied }})
                </span>
              <i v-if="is_processing" class="fas fa-spin fa-spinner ml-2"></i>
            </div>
            <div class="btn-checkout">
                <a :href="checkoutLink"><button class="btn btn-normal" :style="checkoutStyle" :disabled="is_processing || is_error">Checkout</button></a>
            </div>
        </div>
    </div>
</template>

<script>
import GetTextColor from '../../mixins/GetTextColor';
import CurrencyNumber from '../../mixins/CurrencyNumber';

export default {
    watch: {
        'add_to_cart.variation_id': {
            handler(variation_id) {
                let variation = _.find(this.available_variations, (variation) => {
                    return variation.id === variation_id;
                });

                if (variation) {
                    this.add_to_cart.unit_price = variation.price;
                    this.add_to_cart.unit_price_stored = variation.price_stored;

                    if (variation.quantity === 1) {
                        this.add_to_cart.quantity = variation.quantity;
                        this.disabling.quantity = true;
                    }

                }
            },
            deep: true
        },
    },
    mixins: [
        GetTextColor,
        CurrencyNumber,
    ],
    props: {
        customisation: {
            type: Object,
            required: true
        },
    },
    data() {
        return {
            business: [],
            variations: [],
            discount: [],
            is_processing: false,
            is_disabled: false,
            is_error: false,
            errors:[],
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
        };
    },
    mounted() {
        this.business = Business;
        this.discount = Discount;
        this.variations = Variations;

        let vars = [];
        let i = 0;
        _.each(this.variations, (variation) => {
            this.errors[i] = [];
            vars.push(variation);
            i++;
        });
        this.variations = vars;

    },
    methods: {
        productLink(variationId) {
            return this.getDomain(this.business.id + '/product/' + variationId, 'shop')
        },

        async deleteProdFromCart(productId) {
            this.is_processing = true;
            await axios.delete(this.getDomain(this.business.id + '/ajax/cart/' + productId, 'shop')).then(({data}) => {
                location.reload();
            });
            this.is_processing = false;
        },
        updateCart(productId, quantity, variation) {
            console.log("update");
            this.is_processing = true;
            this.is_error = false;
            this.errors[variation] = {};

            if (this.variations[variation].model.quantity != null) {
                if (quantity > this.variations[variation].model.quantity) {
                    this.errors[variation].quantity = "The total amount of inventory available is " + this.variations[variation].model.quantity;
                    this.is_processing = false;
                    this.is_error = true;
                    return;
                }
            }

            _.each(this.errors, (error) => {
                if(Object.keys(error).length > 0) this.is_error = true;
            });

            if (quantity > 0) {
                let submissionData = {
                    quantity: quantity,
                    _method: 'put'
                };

                axios.post(this.getDomain(this.business.id + '/ajax/cart/' + productId, 'shop'), submissionData).then(({data}) => {
                    this.discount = data.discount;

                    let quantityCart = 0;
                     _.each(this.variations, (variation) => {
                        quantityCart += parseInt(variation.cart.quantity);
                    });

                    const element_quantity = document.getElementById('basket-quantity');
                    element_quantity.innerHTML = quantityCart;
                    element_quantity.setAttribute('data-value', quantityCart);
                });
                this.is_processing = false;
                this.is_disabled = false;
            } else {
                this.is_disabled = true;
                this.discount.amount = 0;
                this.is_processing = false;
            }
        },
        decreaseQuantity(productId, quantity, variation){
            if(quantity <= 1)
                return;

            quantity--;

            this.variations[variation].cart.quantity = quantity;

            this.updateCart(productId, quantity, variation);
        }
        ,
        increaseQuantity(productId, quantity, variation){
            quantity++;

            this.variations[variation].cart.quantity = quantity;

            this.updateCart(productId, quantity, variation);
        },

    },
    computed: {
        displayTotalAmount() {
            let totalCartAmount = 0;

            _.each(this.variations, (variation) => {
                totalCartAmount += variation.model.price * variation.cart.quantity;
            });

            if (this.discount.amount) {
                totalCartAmount -= this.discount.amount;
            }

            return this.getFormattedAmount(this.business.currency, totalCartAmount);
        },

        currentThemeColors() {
            return this.themeColors[this.safeTheme]
        },
        checkoutStyle() {
            return {
                color: this.currentThemeColors.textColor,
                'background-color': this.currentThemeColors.mainColor,
            }
        },
        safeTheme() {
            let theme = this.customisation.theme

            // fail-safe for incorrect theme name
            if (!this.themeColors[theme]) {
                theme = 'hitpay'
            }

            return theme
        },
        checkoutLink(){
            return this.getDomain(this.business.id + '/checkout', 'shop')
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

          let discountAmount = 0;

          _.each(this.discount.discount_information, discount => {
            discountAmount = discountAmount + discount.discount_amount;
          });

          return this.getFormattedAmount(this.business.currency, discountAmount);
        }
    }
}
</script>
