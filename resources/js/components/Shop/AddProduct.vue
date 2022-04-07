<style scoped>

.form-stack {
    position: relative;
    display: -webkit-inline-box;
    display: inline-flex;
    vertical-align: middle;
    width: 100%;
}

.form-stack > .form-control {
    width: 100%;
    position: relative;
    -webkit-box-flex: 1;
    flex: 1 1 auto;
}

.form-stack > .form-control:focus {
    z-index: 1;
}

.form-stack {
    -webkit-box-orient: vertical;
    -webkit-box-direction: normal;
    flex-direction: column;
    -webkit-box-align: start;
    align-items: flex-start;
    -webkit-box-pack: center;
    justify-content: center;
}

.form-stack > .form-control:not(:first-child) {
    margin-top: -1px;
    border-top-left-radius: 0;
    border-top-right-radius: 0;
}

.form-stack > .form-control:not(:last-child) {
    border-bottom-right-radius: 0;
    border-bottom-left-radius: 0;
}

/*Variants boxes*/
.radio-toolbar {
    margin:  0px 0px 0px -10px;
    padding:  0px 0px 44px;
}

.radio-toolbar input[type="radio"] {
    opacity: 0;
    position: fixed;
    width: 0;
}

.radio-toolbar label {
    cursor: pointer;
    display: block;
    float: left;
    margin:  0px 0px 10px 10px;
    padding: 4px 12px;
    border: 1px solid #D4D6DD;
    transition: all ease .5s;
    z-index: 10;
    min-width: 44px;
    word-wrap: normal;
    min-height: 36px;
    text-align: center;
}

.form-group{
    margin:  0px;
}

.form-group h2{
    font-size: 24px;
    color: #1E1E1F;
    margin:  0px 0px 30px;
}

.price{
    padding: 0px 0px 36px;
}

.price h6{
    font-size: 36px;
}

.btn-bottom .btn:first-child{
    margin: 0px 24px 0px 0px;
}

@media (max-width: 767px) {
    .form-group h2{
        text-align: center;
        font-size: 20px;
        margin:  0px 0px 30px;
    }

    .radio-toolbar {
        margin:  0px 0px 0px -10px;
        padding:  0px 0px 24px;
    }

    .price{
        padding: 0px 0px 28px;
    }

    .price h6{
        font-size: 28px;
    }
}

</style>

<template>
    <div>
        <div class="p-variation">
            <div id="product_details_errors" v-if="errors.product_details.length > 0" class="alert alert-danger">
                <ul class="list-unstyled mb-0">
                    <li v-for="message in errors.product_details">{{ message }}</li>
                </ul>
            </div>
            <div v-if="product.has_variations" class="form-group">
                <h2 class="p-title">Choose Variant</h2>
                <div class="radio-toolbar clearfix">
                    <template v-for="(variation,key) in available_variations">
                        <input type="radio" :id="variation.id" name="variation" v-model="add_to_cart.variation_id"
                               :value="variation.id"
                               :checked="key==0">
                        <label :for="variation.id">{{variation.description}}</label>
                    </template>
                </div>
            </div>
        </div>
        <div class="price">
            <h6 class="h1 p-title"><span>{{ this.business.currency.toUpperCase() }}</span>{{ (add_to_cart.unit_price).toFixed(2) }}</h6>
        </div>
        <div class="d-flex justify-content-between btn-bottom">
            <button class="btn btn-normal" @click.prevent="addToCart('add')"
                    :style="addToCartStyle"
                    :disabled="is_processing">
                Add to Cart <i v-if="is_processing" class="fas fa-spin fa-spinner ml-2"></i>
            </button>
            <button class="btn btn-normal"
                    :style="buyNowStyle"
                    :disabled="is_processing"
                    @click.prevent="addToCart('buy')"
            >
                Buy Now <i v-if="is_processing" class="fas fa-spin fa-spinner ml-2"></i>
            </button>
        </div>
    </div>
</template>

<script>
import GetTextColor from '../../mixins/GetTextColor'

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
        GetTextColor
    ],
    props: {
        customisation: {
            type: Object,
            required: true
        },
    },

    data() {
        return {
            add_to_cart: {
                variation_id: '',
                unit_price: 0,
                unit_price_stored: 0,
                quantity: 1,
                remark: '',
            },
            available_variations: [],
            errors: {
                product_details: [],
            },
            is_processing: false,
            disabling: {
                quantity: false,
            },
            notes: {
                shipping: '',
            },
            processing_step: 1,
            product: {
                has_variations: false,
            },
            text: {
                button_amount: '',
            },
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
        this.product = Product;

        this.product.price = this.product.price.toFixed(2);

        if (this.product.has_variations) {
            _.each(this.product.variations, (variation) => {
                if (!(this.product.is_manageable && variation.quantity < 1)) {
                    this.available_variations.push(variation);

                    if (this.add_to_cart.variation_id === '') {
                        this.add_to_cart.variation_id = variation.id;
                        this.add_to_cart.unit_price = variation.price;
                        this.add_to_cart.unit_price_stored = variation.price_stored;

                        if (variation.quantity === 1) {
                            this.add_to_cart.quantity = variation.quantity;
                            this.disabling.quantity = true;
                        }
                    }
                }
            });
        } else {
            this.add_to_cart.variation_id = this.product.variations[0].id;
            this.add_to_cart.unit_price = this.product.variations[0].price;
            this.add_to_cart.unit_price_stored = this.product.variations[0].price_stored;

            if (this.product.variations[0].quantity === 1) {
                this.add_to_cart.quantity = this.product.variations[0].quantity;
                this.disabling.quantity = true;
            }
        }

        this.is_initiating = false;
    },

    methods: {
        addToCart(method) {
            this.is_processing = true;

            this.errors.product_details = [];

            if (this.add_to_cart.quantity === '') {
                this.errors.product_details.push('The quantity can\'t be empty');
            }

            if (this.errors.product_details.length > 0) {
                this.scrollTo('#product_details_errors');
                this.is_processing = false;

                return;
            }

            let submissionData = {
                variation_id: this.add_to_cart.variation_id,
                quantity: this.add_to_cart.quantity,
                remark: this.add_to_cart.remark,
            };

            axios.post(this.getDomain(this.business.id + '/ajax/cart', 'shop'), submissionData).then(({data}) => {
                if (method === 'buy') {
                    window.location = this.getDomain(this.business.id + '/cart', 'shop');
                } else if (method === 'add') {
                    let quantity = 0;
                    _.forEach(Object.entries(data.products), (value) => {
                        quantity += parseInt(value[1].quantity);
                    });

                    document.getElementById('basket-quantity').innerHTML = quantity;
                    document.getElementById('basket-quantity').setAttribute('data-value', quantity);
                    document.getElementById('basket-quantity').setAttribute('class', 'cart-quantity');
                }
                this.is_processing = false;

                this.showDialogAddToCart();

            }).catch(({error}) => {
                _.forEach(error.response.data, (value) => {
                    this.errors.product_details.push(_.first(value));
                });

                this.scrollTo('#product_details_errors');
                this.is_processing = false;
            });

            umami('add_cart');
        },
        showDialogAddToCart() {
            let imageSrc  = "";
            if(this.product.images.length > 0){
                imageSrc =  ""+this.product.images[0].url;
            }else{
                imageSrc = "/hitpay/images/product.jpg";
            }

            console.log(this.product);
            let content = "<div class= 'thumbnail'>"
                        + "<img src='"+imageSrc+"' class='product-img-small'>"
                        + "</div>"
                        + "<div class= 'product-title'>"
                        + this.product.name
                        + "</div>"
                        + "<div class= 'cart-quantity'>"
                        + "Qty: 1"
                        + "</div>";

            document.getElementById('dl-add-to-cart-body').innerHTML = content;
            document.getElementById('dialog-add-cart').style.display = "block";

            setTimeout(function() {
               if(document.getElementById('dialog-add-cart') != null){
                   document.getElementById('dialog-add-cart').style.display = "none";
               }
            }, 5000);
        }
    },
    computed: {
        currentThemeColors() {
            return this.themeColors[this.safeTheme]
        },
        addToCartStyle() {
            return {
                color: this.currentThemeColors.mainColor,
                'border': '1px solid ' + this.currentThemeColors.mainColor,
            }
        },
        buyNowStyle() {
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
    },
}
</script>
