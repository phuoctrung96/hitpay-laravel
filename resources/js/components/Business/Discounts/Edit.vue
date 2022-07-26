<template>
  <div class="invoice-section">
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
              <input class="form-check-input" v-model="form.type" type="radio" name="type" id="percent_discount" value="percent" checked required @change="changeBannerText">
              <label class="form-check-label" for="percent_discount">
                Percentage(%)
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input"  v-model="form.type" required  type="radio" name="type" id="fixed_discount" value="fixed" @change="changeBannerText">
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
            <label>Discount applies to: <span class="text-danger">*</span></label>

            <div class="form-check">
              <input class="form-check-input" v-model="form.discount_type" type="radio" name="discount_type"
                     id="promotion_type_all_products" :value="promotion_type_all_products" checked required @change="changeDiscountType">
              <label class="form-check-label" for="promotion_type_all_products">All products</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" v-model="form.discount_type" type="radio" name="discount_type"
                     id="promotion_type_specific_categories" :value="promotion_type_specific_categories" checked required @change="changeDiscountType">
              <label class="form-check-label" for="promotion_type_specific_categories">Specific product categories</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" v-model="form.discount_type" type="radio" name="discount_type"
                     id="promotion_type_specific_products" :value="promotion_type_specific_products" checked required @change="changeDiscountType">
              <label class="form-check-label" for="promotion_type_specific_products">Specific products</label>
            </div>

            <span class="invalid-feedback d-block" role="alert" v-if="errors.discount_type">{{ errors.discount_type }}</span>
          </div>

          <div class="form-group" v-if="form.discount_type === promotion_type_specific_categories">
            <label id="added_categories">Pick specific product categories: <span class="text-danger">*</span></label>
            <multiselect v-model="added_categories" :options="allCategories" :multiple="true" :close-on-select="true"
                         :clear-on-select="false" placeholder="Choose categories" label="name"
                         track-by="name" :max="5">
            </multiselect>

            <span class="invalid-feedback d-block" role="alert" v-if="errors.added_categories">{{ errors.added_categories }}</span>
          </div>

          <div class="mb-4" v-if="form.discount_type === promotion_type_specific_products">
            <label>Pick specific products: <span class="text-danger">*</span></label>
            <div class="add-product-section bg-light" id="added_products">
              <div class="top-section">
                <div class="table-items">
                  <div class="lg-title">
                    <div class="field search">Item</div>
                    <div class="field price">Price</div>
                  </div>
                  <template v-for="(item, key) in added_products">
                    <div class="item-add-product d-lg-flex align-items-center">
                      <div class="field search">
                        <label class="title">Item</label>
                        <input v-if="!added_products[key].product" id="searchInput"
                               v-model="search_products_key[key]"
                               class="form-control" @keyup="searchProduct(key)">
                        <input v-else type="text" class="form-control" disabled
                               :value="added_products[key].product.name+' '+ (item.variation.description ? item.variation.description : '')">
                        <template
                          v-if="!added_products[key].product && (search_products_key[key] && search_products_key[key]!=='') && !is_searching_product">
                          <div :style="outerDropDown">
                            <div :style="innerDropDown" class="is-dropdown-menu border shadow-sm">
                              <template v-if="search_product.search_results.length > 0">
                                <div v-for="(product, index) in search_product.search_results"
                                     class="card-body p-3 bg-white"
                                     :class="{'border-top' : index !== 0}">
                                  <template v-if="product.variations.length > 1">
                                    <p class="font-weight-bold mb-2">{{ product.name }}</p>
                                    <template v-for="(variation, index) in product.variations">
                                      <div class="py-2" :class="{
                                                                                                  'border-top': product.variations.length !== index,
                                                                                              }">
                                        <ul class="list-unstyled small mb-0"
                                            v-if="product.variation_key_1 || product.variation_key_2 || product.variation_key_3">
                                          <li v-if="product.variation_key_1">
                                            {{ product.variation_key_1 }}:
                                            <span class="text-muted">{{
                                                variation.variation_value_1
                                              }}</span>
                                          </li>
                                          <li v-if="product.variation_key_2">
                                            {{ product.variation_key_2 }}:
                                            <span class="text-muted">{{
                                                variation.variation_value_2
                                              }}</span>
                                          </li>
                                          <li v-if="product.variation_key_3">
                                            {{ product.variation_key_3 }}:
                                            <span class="text-muted">{{
                                                variation.variation_value_3
                                              }}</span>
                                          </li>
                                        </ul>
                                        <p v-if="product.is_manageable" class="small mb-1">
                                          Quantity
                                          Available: {{ variation.quantity }}</p>
                                        <p class="small mb-0">
                                                        <span class="text-muted">
                                                          {{currency_display}} {{ Number(variation.price).toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,')}}
                                                        </span>
                                        </p>
                                        <div v-if="variation.is_show_add">
                                          <button v-if="(product.is_manageable && variation.quantity > 0 || !product.is_manageable)"
                                                  class="btn btn-sm px-3 mt-2"
                                                  :key="key"
                                                  :style="{backgroundColor: mainColor, color: 'white'}"
                                                  @click="addProduct(key,product, variation)">
                                            <img src="/icons/ico-plus-white.svg" class="icon-plus"> Add
                                          </button>
                                        </div>
                                      </div>
                                    </template>
                                  </template>
                                  <template v-else>
                                    <p class="font-weight-bold mb-0">{{ product.name }}</p>
                                    <p v-if="product.variations[0].description"
                                       class="small mb-1">
                                      {{ product.variations[0].description }}</p>
                                    <p v-if="product.is_manageable" class="small mb-1">Quantity
                                      Available:
                                      {{ product.variations[0].quantity }}</p>
                                    <p class="small mb-0">
                                                    <span class="text-muted">
                                                      {{currency_display}}{{Number(product.variations[0].price).toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,')}}
                                                    </span>
                                    </p>
                                    <div v-if="product.variations[0].is_show_add">
                                      <button
                                        v-if="(product.is_manageable && product.variations[0].quantity > 0 || !product.is_manageable)"
                                        class="btn btn-sm px-3 mt-2"
                                        :style="{backgroundColor: mainColor, color: 'white'}"
                                        @click="addProduct(key,product,product.variations[0])">
                                        <img src="/icons/ico-plus-white.svg" class="icon-plus"> Add
                                      </button>
                                    </div>
                                  </template>
                                </div>
                              </template>
                              <template v-else>
                                <div class="card-body p-4">
                                  <p class="text-muted font-italic mb-0">No product found matches the keywords.</p>
                                </div>
                              </template>
                            </div>
                          </div>
                        </template>
                        <template v-if="!added_products[key].product && is_searching_product && search_products_key[key]">
                          <div class="card-body p-4">
                            <p class="text-muted font-italic mb-0"><i class="fas fa-spin fa-spinner"></i>
                            </p>
                          </div>
                        </template>
                      </div>
                      <div class="field price">
                        <label class="title">Price</label>
                        <input type="text" class="form-control" disabled
                               :value="added_products[key].variation ? added_products[key].variation.price.toFixed(2) : ''">
                      </div>
                      <div class="field delete">
                        <a class="btn-delete" v-if="key!=0" href="#" @click="removeProduct($event, key)"><img src="/images/delete_icon.svg" alt="delete"></a>
                      </div>
                    </div>
                  </template>
                </div>
                <span v-if="errors.product_exist" class="invalid-feedback d-block mb-4" role="alert">{{ errors.product_exist }}</span>
                <button class="btn btn-plus"
                        :style="{border : '1px solid '+mainColor, color: mainColor, backgroundColor: 'white', fontSize: '14px'}"
                        @click="addProductSlot($event)" v-if="added_products.length<30"> <span>Add Item</span>
                </button>
              </div>
            </div>
            <span class="invalid-feedback d-block" role="alert" v-if="errors.added_products">{{ errors.added_products }}</span>
          </div>

          <div class="form-group">
            <label for="agree"><input v-model="form.is_promo_banner" id="is_promo_banner" type="checkbox" value="agree" class="form-check-label"/> Show as Promo Banner</label>
            <input id="banner_text" class="form-control bg-light" type="text" v-model="form.banner_text" title="Promo Banner" placeholder="Enter text to display" v-if="form.is_promo_banner">
          </div>
        </form>
        <button id="createBtn" class="btn btn-success btn-lg btn-block mb-3 shadow-sm" @click="updateDiscount()" :disabled="is_busy">
          {{'Save Changes'}}
          <i class="fas fa-spin fa-spinner" :class="{
                        'd-none' : !is_busy
                    }"></i></button>
      </div>
    </div>
  </div>
</template>

<script>
import isNumber from '../../../mixins/NumberValidationMixin';
import Multiselect from 'vue-multiselect';

Vue.component('multiselect', Multiselect)

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
        banner_text: 'Use discount for Off',
        discount_type: 1,
        applies_to_ids: [],
      },
      discount_id: null,
      mainColor: '#011B5F',
      innerDropDown: {
        'position': 'absolute',
        'z-index': '100',
        'width': '100%'
      },
      outerDropDown: {
        'position': 'relative',
        'width': '100%'
      },
      currency_display: 'SGD',
      allCategories: [],
      added_products: [{}],
      added_categories: [],
      search_products_key: [],
      search_product: {
        keywords: '',
        search_results: [],
        timeout: null
      },
      is_searching_product: false,
      promotion_type_all_products: 1,
      promotion_type_specific_categories: 2,
      promotion_type_specific_products: 3,
      errors: {},
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
        if (values.value !== null  && values.value !== '' && values.type !== 'percent') {
          let indexOfPeriodForPrice = values.value.toString().indexOf('.');
          let decimalsLengthForPrice = values.value.substr(indexOfPeriodForPrice);

          if (decimalsLengthForPrice && decimalsLengthForPrice.length > 3) {
            this.errors.value = 'The fixed amount can\'t have more than two decimals.';
          }

          if (Number.parseInt(values.value.toString()) < 0) {
            this.errors.value = 'The fixed amount can\'t have less than 0.';
          }
        }

        if (values.value !== null  && values.value !== '' && values.type === 'percent') {
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
    const currentURL = window.location.href;

    const splittedURL = currentURL.split("/");

    // console.log(splittedURL);

    this.discount_id = splittedURL[6];

    this.getDiscount();
  },
  methods: {
    updateDiscount(){
      this.errors = {};
      this.is_busy = true;

      if ( this.form.minimum_cart_amount === 0 || this.form.minimum_cart_amount === null ){
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
      if (this.form.value !== null  && this.form.value !== '' && this.form.type !== 'percent') {
        let indexOfPeriodForPrice = this.form.value.toString().indexOf('.');
        let decimalsLengthForPrice = this.form.value.substr(indexOfPeriodForPrice);

        if (decimalsLengthForPrice && decimalsLengthForPrice.length > 3) {
          this.errors.value = 'The fixed amount can\'t have more than two decimals.';
        }

        if (Number.parseInt(this.form.value) < 0) {
          this.errors.value = 'The fixed amount can\'t have less than 0.';
        }
      }

      if (this.form.value!== null  && this.form.value !== '' && this.form.type === 'percent') {
        let value = this.form.value.toString();
        if (Number.parseInt(value) > 100) {
          this.errors.value = 'The percentage can\'t have more than 100%.';
        }

        if (Number.parseInt(value) < 0) {
          this.errors.value = 'The percentage can\'t have less than 0.';
        }
      }

      if (this.form.discount_type === this.promotion_type_specific_categories) {
        if (this.added_categories.length <= 0) {
          this.errors.added_categories = 'Please choose any categories';
        }
      }

      if (this.form.discount_type === this.promotion_type_specific_products) {
        if (this.added_products.length <= 0) {
          this.errors.added_products = 'Please choose any products';
        }
      }

      // populated applies_to
      if (this.form.discount_type === this.promotion_type_specific_categories) {
        let that = this;

        this.form.applies_to_ids = [];

        this.added_categories.forEach(function(item) {
          that.form.applies_to_ids.push(item.id);
        })

        if (this.form.applies_to_ids.length === 0) {
          this.errors.added_categories = 'Please choose any categories';
        }
      }

      if (this.form.discount_type === this.promotion_type_specific_products) {
        let that = this;

        this.form.applies_to_ids = [];

        this.added_products.forEach(function(item) {
          if (item.variation && item.variation.id) {
            that.form.applies_to_ids.push(item.variation.id);
          }
        });

        if (this.form.applies_to_ids.length === 0) {
          this.errors.added_products = 'Please choose any products';
        }
      }

      if (this.form.discount_type === this.promotion_type_all_products) {
        this.form.applies_to_ids = [];
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

        axios.put(this.getDomain('v1/business/' + Business.id + '/discount/' + this.discount_id, 'api'), this.form, {
          withCredentials: true
        }).then(({data}) => {
          window.location.href = this.getDomain('business/' + Business.id + '/discount', 'dashboard');
        }).catch(({response}) => {
          if (response.status === 422) {
            _.forEach(response.data.errors, (value, key) => {
              if (key === 'applies_to_ids') {
                if (this.form.discount_type === this.promotion_type_specific_products) {
                  key = 'added_products';
                }

                if (this.form.discount_type === this.promotion_type_specific_categories) {
                  key = 'added_categories';
                }
              }

              this.errors[key] = _.first(value);
            });
            this.showError(_.first(Object.keys(this.errors)));
          }
        });
      }
    },

    getDiscount() {
      this.is_busy = true;

      axios.get(this.getDomain('v1/business/' + Business.id + '/discount/' + this.discount_id, 'api'),{
        withCredentials: true
      }).then(({data}) => {
        this.form.id = data.id
        this.form.name = data.name;
        this.form.minimum_cart_amount = Number(data.minimum_cart_amount/100).toFixed(2);
        this.form.fixed_amount = Number( data.fixed_amount/100).toFixed(2);
        this.form.percentage = data.percentage;
        this.form.type = data.percentage?'percent': 'fixed';
        this.form.value = data.percentage? (data.percentage * 100).toFixed(2): this.form.fixed_amount;
        this.form.is_promo_banner = data.is_promo_banner;
        this.form.banner_text = data.banner_text;
        this.form.discount_type = data.discount_type;
        this.form.applies_to_ids = data.applies_to_ids;

        if (this.form.discount_type === this.promotion_type_specific_categories) {
          this.getCategories();

          // set categories selected
          let that = this;
          this.form.applies_to_ids.forEach(function(item) {
            that.added_categories.push(item);
          });
        }

        if (this.form.discount_type === this.promotion_type_specific_products) {
          // set product selected
          this.added_products = this.form.applies_to_ids;
          this.$forceUpdate();

          // reset applies to ids
          this.form.applies_to_ids.map(function(item) {
            return item.variation.id;
          });
        }

        this.is_busy = false;
      }).catch(({response}) => {
        console.log(response);
        this.is_busy = false;
      });
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

      if(this.form.type === 'percent') {
        this.form.banner_text = 'Get ' + parseInt(value) + '% off with minimum purchase of $' + miniCartAmount;
      }else{
        this.form.banner_text = 'Get $' + parseInt(value) + ' off with minimum purchase of $' + miniCartAmount;
      }
    },

    getCategories() {
      this.is_busy = true;

      axios.get(this.getDomain('v1/business/' + Business.id + '/product-category', 'api'),{
        withCredentials: true
      }).then(({data}) => {
        this.is_busy = false;
        this.allCategories = data;
      }).catch(({response}) => {
        console.log(response);
        this.is_busy = false;
      });
    },

    changeDiscountType() {
      if (typeof this.form.discount_type === 'string') {
        this.form.discount_type = parseInt(this.form.discount_type);
      }

      if (this.form.discount_type === this.promotion_type_specific_categories) {
        this.getCategories();
      }
    },

    searchProduct(key) {
      this.is_searching_product = true;

      clearTimeout(this.search_product.timeout);

      this.search_product.timeout = setTimeout(() => {
        if (this.search_products_key[key] === '') {
          this.search_product.search_results = [];
        } else {
          this.search_product.search_results = [];
          axios.post(this.getDomain('business/' + Business.id + '/point-of-sale/', 'dashboard') + 'product', {
            keywords: this.search_products_key[key],
          }).then(({data}) => {
            this.is_searching_product = false;
            let data_search = data;
            data_search.forEach((item) => {
              item.variations.forEach((variation) => {
                variation.is_show_add = true;
                if (this.added_products.find(x => (x.variation && x.variation.id === variation.id))) {
                  variation.is_show_add = false;
                }
              });
            });
            this.search_product.search_results = data_search;
          });
        }
      }, 500);
    },

    addProduct(key, product, variation = null) {
      if (!this.added_products.find(x => (x.variation && x.variation.id === variation.id))) {
        this.added_products[key].product = product;
        this.added_products[key].variation = variation;
        this.search_products_key[key]= '';
        this.$forceUpdate();
      }
    },

    addProductSlot(event, key) {
      event.preventDefault();

      this.added_products.push({});
    },

    removeProduct(event, key) {
      event.preventDefault();

      this.added_products.splice(key, 1);
    },
  }
}
</script>
