<style scoped>

.variation-content p.variation-item{ 
    border: #D4D6DD 1px solid;
    float: left;
    margin: 0px 0px 10px 10px;
    padding: 6px 12px 6px 12px;
    color: #6D6E73;
}

.radio-toolbar {
    margin:  0px 0px 0px -10px;
}

.radio-toolbar input[type="radio"] {
    opacity: 0;
    position: fixed;
    width: 0;
}

.radio-toolbar label{
    display: block;
    float: left;
    border: 1px solid #D4D6DD;
    margin:  0px 0px 10px 10px;
    max-width: calc(100% - 10px);
    padding: 0px 12px 0px;
}

.radio-toolbar label span{
    cursor: pointer;
    padding: 5px 0px;
    display: block;
    transition: all ease .5s;
    z-index: 10;
    min-width: 44px;
    min-height: 36px;
    text-align: center;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    width: 100%;
}

.variation.active .radio-toolbar label span{
    white-space: normal;
}

.meta-product .p-title{
    font-size: 20px;
}

.meta-product .radio-toolbar{
    padding:  0px 0px 0px;
    margin-bottom: 20px;
}

.meta-product .variation .see-more{
    padding:  6px 0px 38px;
}

.meta-product .variation .see-more button{
    background: #FFF;
    color: #002771;
    text-decoration: underline;
    border: 0;
    padding:  0;
    display: block;
}

@media (max-width: 767px) {
    .meta-product .p-title{
        font-size: 18px;
    }
}

</style>

<template>
  <div class="product-item-card product-featured-card card">
      <div class="thumbnail" :style="{'background-image': 'url('+product.imageSrc+')'}">
          <a :href="productLink(product.id)" class="meta-top">
              <template v-if="product.imageSrc">
                  <img :src="product.imageSrc"
                       class="card-img-top border-bottom3"
                       :alt="product.name">
              </template>
              <template v-else>
                  <img src="/hitpay/images/product.jpg"
                       class="card-img-top border-bottom"
                       alt="product">
              </template>
          </a>
      </div>
      <div class="meta-product">
          <h5 class="product-title"><a :href="productLink(product.id)">{{ product.name }}</a></h5>
          <div id="product_details_errors" v-if="errors.product_details.length > 0" class="alert alert-danger">
                <ul class="list-unstyled mb-0">
                    <li v-for="message in errors.product_details">{{ message }}</li>
                </ul>
            </div>
          <div class="variation clearfix" id="variation-item" :class="{'active': isAddClass}">
              <div class="radio-toolbar clearfix" :style="variatonStyle" ref="variations">
                    <template v-for="(variation,key) in available_variations">
                        <input type="radio" :id="variation.id" :name="'variation' + product.id + key" v-model="add_to_cart.variation_id"
                               :value="variation.id" :checked="key===0">
                        <label :for="variation.id"><span>{{variation.description}}</span></label>
                    </template>
                </div>
                <div v-if="show_see_more" class="see-more">
                    <button @click="showAllVariation()">See more</button>
                </div>
          </div> 
          <div class="d-flex justify-content-between card-for">
              <h6 class="p-title my-auto"><span>$</span>{{ (add_to_cart.unit_price).toFixed(2) }}</h6>
              <div v-if="product.available" class="my-auto">
                  <button type="button" class="btn" :style="btnStyle" @click.prevent="addToCart('add')">{{text_add_cart}}
                  </button>
              </div>
              <small v-else class="text-center">Product is out of stock</small>
          </div>
      </div>
  </div>
</template>

<script>
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

                this.text_add_cart = 'Add to cart';
            },
            deep: true
        },
    },
    props: {
        product: {
            required: true
        },
        business: {
            required: true
        },
        currentThemeColors: {
            required: true
        }
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
            errors: {
                product_details: [],
            },
            available_variations: [],
            show_see_more: false,
            max_height: 'auto',
            is_processing: false,
            text_add_cart: 'Add to cart',
            isAddClass: false,
      }
    },
    mounted() {
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

        if(this.product.has_variations){
            this.show_see_more = true;
        }

        this.$ready(() => {
            let height = this.$refs.variations.clientHeight;
            if( height > 100){
                this.show_see_more = true;
                this.max_height = '90px';
            }else{
                this.show_see_more = false;
                this.max_height = '90px';
            }
        })
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
            
            const element_quantity = document.getElementById('basket-quantity');
            
            axios.post(this.getDomain(this.business.id + '/ajax/cart', 'shop'), submissionData).then(({data}) => {
                let quantity = 0;
                _.forEach(Object.entries(data.products), (value) => {
                    quantity += parseInt(value[1].quantity);
                });
                
                element_quantity.innerHTML = quantity;
                element_quantity.setAttribute('data-value', quantity);
                element_quantity.setAttribute('class', 'cart-quantity');

                //this.scrollTo('#app');

                this.text_add_cart = 'Added to cart';
                this.is_processing = false;
                
                this.showDialogAddToCart();
            }).catch(({error}) => {
                if(error !== undefined) {
                _.forEach(error.response.data, (value) => {
                        this.errors.product_details.push(_.first(value));
                    });

                    this.scrollTo('#product_details_errors');
                    this.is_processing = false;
                }
            });

            umami('add_cart');
        },
        productLink(id) {
            return '/' + this.business.identifier + '/product/' + id;
        },
        showAllVariation() {
            this.show_see_more = false;
            this.max_height = 'auto';

            this.isAddClass = true;
        },
        load(){
            console.log("load");
        },
        showDialogAddToCart() {
            let imageSrc  = "";
            if(this.product.imageSrc){
                imageSrc =  this.product.imageSrc;
            }else{
                imageSrc = "/hitpay/images/product.jpg"
            }

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
        btnStyle() {
            return {
                color: this.currentThemeColors.textColor,
                backgroundColor: this.currentThemeColors.mainColor,
                borderColor: this.currentThemeColors.mainColor
            };
        },
        variatonStyle() {
            return {
                height: this.max_height,
                backgroundColor: '#FFF',
                overflow: 'hidden',
                transition: 'all .3s ease'
            }
        }
    }
}
</script>
