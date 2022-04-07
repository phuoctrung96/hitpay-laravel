<style scoped>

.category {
    line-height: 1;
    margin: 0 auto;
}

.category__list {
    position: relative;
    display: -webkit-flex;
    display: flex;
    -webkit-flex-wrap: wrap;
    flex-wrap: wrap;
    margin: 0;
    padding: 0;
    list-style: none;
    border-bottom: 1px solid #D4D6DD;
    margin: 0px 0px 76px;
    overflow: auto;
    flex-wrap: nowrap;
}

.category__item {
    display: block;
    margin:  0px 8px;
}

.category__item:first-child{
    margin:  0px 0px;
}

.category--prospero .category__link {
    position: relative;
    display: block;
    padding: 12px 24px 12px;
    text-align: center;
    transition: color 0.3s;
    font-size: 18px;
    cursor: pointer;
}

/*.category--prospero .category__link:hover,
.category--prospero .category__link:focus {
    color: black;
}*/

.category--prospero .category__link::before {
    content: '';
    position: absolute;
    left: 0;
    bottom: 0;
    width: 100%;
    height: 2px;
    background: black;
    transform: scale3d(0, 1, 1);
    transition: transform 0.1s;
}

.variation {
    min-height: 120px;
}

.variation-content{
    width: 100%;
    float: left;
    font-size: 15px;
    margin:  0px 0px 0px -10px;
}

.multiple-variants{
    font-size: 16px;
    color: #6D6E73;
    padding: 0px 0px 18px;
    margin:  0px;
}

/* .variation-content{
    max-height: 85px;
    overflow: hidden;
    transition: all .3s ease;
} */

.ic-checked-variation{
    width: 13px;
    height: auto;
    margin: 0px 12px 0px 0px;
    position: relative;
    top: -1px;
}

.all-products .product-item-card{
    box-shadow: 0 4px 30px rgba(89,89,92,.15);
    padding-bottom: 35px;
}

.all-products .product-item-card .px-4{
    padding-left: 24px !important;
    padding-right: 24px !important;
}

.meta-bottom{
    padding:  20px 0px 0px;
}

.meta-bottom .p-title{
    font-size: 20px;
}

@media (max-width: 767px) {
    .category__list {
        margin: 0px 0px 47px;
    }

    .category__item {
        display: block;
        margin:  0px 5px;
    }

    .category__item:first-child{
        margin:  0px 0px;
    }

    .category--prospero .category__link {
        padding: 8px 14px 7px;
        font-size: 15px;
    }

    .multiple-variants{
        padding: 0px 0px 23px;
        font-size: 13px;
    }

    .ic-checked-variation{
        width: 11px;
        margin: 0px 10px 0px 0px;
    }

    .all-products .product-item-card{
        box-shadow: 0 4px 30px rgba(89,89,92,.15);
        padding-bottom: 25px;
        height: auto !important;
    }

    .all-products .product-item-card .px-4{
        padding-left: 18px !important;
        padding-right: 18px !important;
    }

    .meta-bottom{
        padding:  0px 0px 0px;
    }

    .meta-bottom .p-title{
        font-size: 16px;
    }
}

@media (max-width: 767px) {
    .all-products{
        padding-left: 7px;
        padding-right: 7px;
    }
    .all-products .col-product{
        padding-left: 8px;
        padding-right: 8px;
    }
}

</style>
<template>
    <div>
        <div class="container">
            <section class="section section--category mb-3" id="category-list">
                <nav class="category category--prospero">

                    <ul class="category__list">
                        <li class="category__item"
                            :class="{'category__item--current' : 'home' === activeCategory}"
                            @click="updateProducts('home')">
                            <a class="category__link">All</a>
                        </li>
                        <li v-for="(category, index) in categories" class="category__item"
                            :class="{'category__item--current' : index === activeCategory}"
                            @click="updateProducts(index)">
                            <a class="category__link">{{ category.name }}</a>
                        </li>
                    </ul>

                </nav>
            </section>
            <div class="row" v-if="featuredProducts.length > 0">
                <div class="col-12">
                    <h5 class="category-title featured-product-title">Featured Products</h5>
                </div>
            </div>
            <div class="row featured-product" v-if="featuredProducts.length > 0">
                <div v-for="(product,index) in featuredProducts" :key="product.id" class="col-12 col-lg-4 mb-product">
                    <product-featured :product="product" :business="business" :currentThemeColors="currentThemeColors" class="product-images"></product-featured >
                </div>
            </div>
            <div class="row" v-if="products.length > 0">
                <div class="col-12">
                    <h5 class="category-title all-product-title">All Products</h5>
                </div>
            </div>
            <div class="row all-products" v-if="products.length > 0" id="all-product">
                <div v-for="(product,index) in pageOfProducts" :key="product.id" class="col-6 col-md-6 col-lg-4 col-product">
                    <div class="product-item-card product-item-normal card h-100">
                        <div class="thumbnail" :style="{'background-image': 'url('+product.imageSrc+')'}">
                            <a :href="productLink(product.id)">
                                <template v-if="product.images_count > 0">
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
                        <a :href="productLink(product.id)" class="flex-grow-1">
                            <h5 class="product-title px-4">{{ product.name }}</h5>
                            <p v-if="product.variations_count > 1" class="multiple-variants px-4">
                            <img src="/images/checked_icon.svg" alt="checked" class="ic-checked-variation">Multiple variations</p>
                        </a>
                        <div class="d-flex justify-content-between meta-bottom px-4 d-m-block">
                            <h5 class="p-title my-auto">{{ product.showPrice }}</h5>
                            <a v-if="product.available" class="btn-add-cart" :href="productLink(product.id)">
                                <button type="button" class="btn btn-outline-secondary" :style="btnStyle">Add to cart
                                </button>
                            </a>
                            <small v-else class="text-center">Product is out of stock</small>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="p-4">
                <div class="text-center text-muted py-5">
                    <p><i class="fa fas fa-boxes fa-4x"></i></p>
                    <p class="small mb-5">- No products available -</p>
                </div>
            </div>
            <div class="pagination-shop clearfix">
                <jw-pagination :items="products" :maxPages=4 :pageSize=9 @changePage="onChangePage" @click=""></jw-pagination>
            </div>
        </div>
    </div>
</template>

<script>

import GetTextColor from '../../mixins/GetTextColor';
import ProductFeatured from './ProductFeatured.vue';

export default {
  components: { ProductFeatured },
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
            business: [],
            products: [],
            is_processing: false,
            pageOfProducts: [],
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
            categories: [],
            featuredProducts: [],
            activeCategory: 'home'
        };
    },

    mounted() {
        this.business = Business;

        if (window.Products !== undefined) {
            this.products = Products;
        }

        if (window.Categories !== undefined) {
            this.categories = Categories;
        }

        if (window.FeaturedProducts !== undefined) {
            this.featuredProducts = FeaturedProducts;
        }

        let productsWithAttrs = [];
        let productAttrs = ProductAttrs;
        let featuredProductsWithAttrs = [];
        let featuredProductsAttrs = FeaturedProductsAttrs;

        let i = 0;
        _.each(this.products, (product) => {
            product.imageSrc = productAttrs['image'][i];
            product.showPrice = productAttrs['price'][i];
            product.available = productAttrs['available'][i];

            productsWithAttrs.push(product);
            i++;
        });

        this.products = productsWithAttrs;

        let j = 0;
        _.each(this.featuredProducts, (featuredProduct) => {
            featuredProduct.imageSrc = featuredProductsAttrs['image'][j];
            featuredProduct.showPrice = featuredProductsAttrs['price'][j];
            featuredProduct.available = featuredProductsAttrs['available'][j];

            featuredProductsWithAttrs.push(featuredProduct);
            j++;
        });
        this.featuredProducts = featuredProductsWithAttrs;
    },
    methods: {
        onChangePage(pageOfItems) {
            // update page of items
            if(this.pageOfProducts.length == 0){
                this.scrollTo('#app');
            }else{
                this.scrollTo('#all-product');
            }
            this.pageOfProducts = pageOfItems;     
        },
        productLink(id) {
            return '/' + this.business.identifier + '/product/' + id;
        },
        updateProducts(index) {
            this.activeCategory = index;
            let link = '';
            if(this.activeCategory === 'home')
                link = this.getDomain(this.business.id + '/product/category/home','shop');
            else link = this.getDomain(this.business.id + '/product/category/' + this.categories[index].id, 'shop');
            axios.post(link).then(({data}) => {
                this.products = data.products;
                this.featuredProducts = data.featured_products;

                let productsWithAttrs = [];
                let productAttrs = data.product_attrs;

                let i = 0;
                _.each(this.products, (product) => {
                    product.imageSrc = productAttrs['image'][i];
                    product.showPrice = productAttrs['price'][i];
                    product.available = productAttrs['available'][i];

                    productsWithAttrs.push(product);
                    i++;
                });

                this.products = productsWithAttrs;

                let featuredProductsWithAttrs = [];
                let featuredProductsAttrs = data.featured_product_attrs;

                let j = 0;
                _.each(this.featuredProducts, (featuredProduct) => {
                    featuredProduct.imageSrc = featuredProductsAttrs['image'][j];
                    featuredProduct.showPrice = featuredProductsAttrs['price'][j];
                    featuredProduct.available = featuredProductsAttrs['available'][j];

                    featuredProductsWithAttrs.push(featuredProduct);
                    j++;
                });
                this.featuredProducts = featuredProductsWithAttrs;
            });
        },  
    },
    
    computed: {
        currentThemeColors() {
            return this.themeColors[this.safeTheme]
        },
        btnStyle() {
            return {
                color: this.currentThemeColors.textColor,
                backgroundColor: this.currentThemeColors.mainColor,
                borderColor: this.currentThemeColors.mainColor
            };
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
