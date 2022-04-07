<template>
    <div class="poin-of-sale" @click="clickMain">
        <div class="row">
            <div class="col-12 col-sm-12 col-md-12 col-lg-8">
                <div class="btn-tab-group">
                    <a class="btn round-bottom-0" :class="{
                            'btn-outline-primary active': current_tab === 'quick-sale-tab',
                            'btn-light': current_tab !== 'quick-sale-tab'
                        }" @click="changeTab('quick-sale-tab')">Quick Sale
                    </a>
                    <a class="btn round-bottom-0" :class="{
                            'btn-outline-primary active': current_tab === 'products-tab',
                            'btn-light': current_tab !== 'products-tab'
                        }" @click="changeTab('products-tab')">Products
                    </a>
                </div>
            </div>
        </div>
        <div v-if="current_tab === 'quick-sale-tab'" class="row">
            <div class="col-12 col-sm-12 col-md-12 col-lg-8 mb-4">
                <div class="card main-content round-top-0 shadow-sm border-0">
                    <template v-if="terminals.location_id !== null">
                        <div class="card-body border-bottom p-4">Terminal:</div>
                        <div class="card-body bg-light border-bottom p-4">
                            <div v-if="terminals.connected" class="media">
                                <img :src="getDomain('icons/reader/reader-image-small.png')" class="align-self-center mr-3" alt="Terminal" height="48">
                                <div class="media-body">
                                    <button class="btn btn-danger btn-sm float-right" @click="disconnectTerminal(true)">Disconnect</button>
                                    <h6 class="font-weight-bold mt-0">{{ terminals.connected.label }}</h6>
                                    <p class="small text-muted mb-0">IP Address: {{ terminals.connected.ip_address}}</p>
                                    <p class="small text-muted mb-0">Serial Number: {{ terminals.connected.serial_number }}</p>
                                </div>
                            </div>
                            <template v-else-if="terminal_status">
                                <p class="font-weight-bold text-danger">{{ terminal_status }}</p>
                                <button class="btn btn-warning btn-sm" @click="openDiscoverTerminalsModal()">Connect again</button>
                            </template>
                            <button v-else class="btn btn-primary btn-sm" @click="openDiscoverTerminalsModal()">Connect terminal now</button>
                        </div>
                    </template>
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-12 col-lg-6 order-lg-2">
                                <label class="small"><span class="text-uppercase">Remark</span> (Optional)</label>
                                <input id="remark" v-model="quick_sale_remark" class="form-control mb-3" aria-label="Remar" aria-describedby="remarkLabel" autocomplete="off" :disabled="is_processing">
                            </div>
                            <div class="col-12 col-lg-6 order-lg-1">
                                <label class="small text-uppercase">Amount</label>
                                <div class="input-group mb-3">
                                    <input id="amount" v-model="amount" class="form-control border-right-0 text-monospace" placeholder="0.00" aria-label="Amount" aria-describedby="amountLabel" autocomplete="off" @keydown="validateAmountInput($event)" :disabled="is_processing">
                                    <div class="input-group-prepend">
                                        <div class="input-group-text rounded-right text-monospace" id="amountLabel">{{ currency_display }}</div>
                                    </div>
                                </div>
                                <div class="text-monospace bg-light btn-group-vertical rounded w-100 mb-3" role="group">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-light number-pad top-left py-3" @click="appendNumber('1')" :disabled="is_processing">1</button>
                                        <button type="button" class="btn btn-light number-pad py-3" @click="appendNumber('2')" :disabled="is_processing">2</button>
                                        <button type="button" class="btn btn-light number-pad top-right py-3" @click="appendNumber('3')" :disabled="is_processing">3</button>
                                    </div>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-light number-pad py-3" @click="appendNumber('4')" :disabled="is_processing">4</button>
                                        <button type="button" class="btn btn-light number-pad py-3" @click="appendNumber('5')" :disabled="is_processing">5</button>
                                        <button type="button" class="btn btn-light number-pad py-3" @click="appendNumber('6')" :disabled="is_processing">6</button>
                                    </div>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-light number-pad py-3" @click="appendNumber('7')" :disabled="is_processing">7</button>
                                        <button type="button" class="btn btn-light number-pad py-3" @click="appendNumber('8')" :disabled="is_processing">8</button>
                                        <button type="button" class="btn btn-light number-pad py-3" @click="appendNumber('9')" :disabled="is_processing">9</button>
                                    </div>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-light number-pad bottom-left py-3" @click="backspaceNumber()" :disabled="is_processing">&lt;</button>
                                        <button type="button" class="btn btn-light number-pad py-3" @click="appendNumber('0')" :disabled="is_processing">0</button>
                                        <button type="button" class="btn btn-light number-pad bottom-right py-3" @click="appendNumber('.')" :disabled="is_processing">.</button>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 col-lg-6 order-lg-1 mb-3 mt-2">
                                <button class="btn btn-primary btn-lg btn-block" @click="charge" :disabled="is_processing">
                                    Charge {{ amount ? currency_display + Number(amount).toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,') : '' }}
                                    <i v-if="is_processing" class="fas fa-spinner fa-spin"></i>
                                </button>
                                <div v-if="general_error" class="text-center mt-3">
                                    <span class="font-weight-bold text-danger">{{ general_error }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-12 col-md-12 col-lg-4">
                <CardReader/>
            </div>
        </div>
        <div v-else-if="current_tab === 'products-tab'" class="row">
            <div class="col-12 col-lg-8">
                <div class="card main-content round-top-0 shadow-sm mb-3">
                    <div class="p-4">
                        <p class="text-uppercase title">Product</p>
                        <div class="product-search mb-4">
                            <input id="searchInput" v-model="order.keywords" class="form-control" placeholder="Type here to search…" @keyup="searchProduct">
                            <div class="search-result shadow-sm" id="search_product_popup" v-if="product.is_show_popup">
                                <template v-if="order.keywords !== '' && !is_searching_product">
                                    <template v-if="order.search_results.length > 0">
                                        <div v-for="(product, index) in order.search_results" class="item" :class="{
                                            'border-top' : index !== 0
                                        }">
                                            <template v-if="product.variations.length > 1">
                                                <p class="title-product hl">{{ product.name }}</p>
                                                <template v-for="(variation, index) in product.variations">
                                                    <div class="variation d-flex justify-content-between py-2" :class="{'border-top': product.variations.length !== index,}">
                                                        <div class="information my-auto">
                                                            <ul class="list-unstyled mb-0" v-if="product.variation_key_1 || product.variation_key_2 || product.variation_key_3">
                                                                <li v-if="product.variation_key_1">{{ product.variation_key_1 }}:
                                                                    <span class="text-muted">{{ variation.variation_value_1 }}</span>
                                                                </li>
                                                                <li v-if="product.variation_key_2">{{ product.variation_key_2 }}:
                                                                    <span class="text-muted">{{ variation.variation_value_2 }}</span>
                                                                </li>
                                                                <li v-if="product.variation_key_3">{{ product.variation_key_3 }}:
                                                                    <span class="text-muted">{{ variation.variation_value_3 }}</span>
                                                                </li>
                                                            </ul>
                                                            <!-- <p v-if="product.is_manageable" class="small mb-1">Quantity Available: {{ variation.quantity }}</p> -->
                                                            <p class="mb-0">
                                                                <span class="text-muted">{{ currency_display }} {{ Number(variation.price).toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,') }}</span>
                                                            </p>
                                                        </div>
                                                        <button v-if="(product.is_manageable && variation.quantity > 0 || !product.is_manageable)" class="btn btn-sm btn-primary my-auto" @click="addProduct(variation.id)"><img src="/icons/ico-plus.svg" class="icon-plus"> Add</button>
                                                    </div>
                                                </template>
                                            </template>
                                            <template v-else>
                                                <div class="d-flex justify-content-between">
                                                    <div class="information my-auto">
                                                        <p class="title-product mb-0">{{ product.name }}</p>
                                                        <p v-if="product.variations[0].description" class="small mb-1">{{ product.variations[0].description }}</p>
                                                        <!-- <p v-if="product.is_manageable" class="small mb-1">Quantity Available: {{ product.variations[0].quantity }}</p> -->
                                                        <p class="price mb-0">
                                                            <span class="text-muted">{{ currency_display }} {{ Number(product.variations[0].price).toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,') }}</span>
                                                        </p>
                                                    </div>
                                                    <button v-if="(product.is_manageable && product.variations[0].quantity > 0 || !product.is_manageable)" class="btn my-auto btn-sm btn-primary" @click="addProduct(product.variations[0].id)"><img src="/icons/ico-plus.svg" class="icon-plus"> Add</button>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                    <template v-else>
                                        <div class="card-body p-4">
                                            <p class="text-muted font-italic mb-0">No product found matches the keywords.</p>
                                        </div>
                                    </template>
                                </template>
                                <template v-if="order.keywords !== '' && is_searching_product">
                                    <div class="card-body p-4">
                                        <p class="text-muted font-italic mb-0"><i class="fas fa-spin fa-spinner"></i></p>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <!-- <template v-else>
                            <div class="card-body p-4">
                                <p class="text-muted font-italic mb-0">Please enter keywords to search...</p>
                            </div>
                        </template> -->
                        <template>
                            <section class="section d-none d-lg-block section--category mb-3" id="category-list" v-if="featured_products.length > 0">
                                <nav class="category category--prospero">
                                    <ul class="category__list">
                                        <li class="category__item"
                                            :class="{'category__item--current' : 'home' === activeCategory}"
                                            @click="updateFeaturedProducts('home')">
                                            <a class="category__link">All</a>
                                        </li>
                                        <li v-for="(category, index) in categories" class="category__item"
                                            :class="{'category__item--current' : index === activeCategory}"
                                            @click="updateFeaturedProducts(index)">
                                            <a class="category__link">{{ category.name }}</a>
                                        </li>
                                    </ul>

                                </nav>
                            </section>
                            <div class="row d-none d-lg-flex featured-product" v-if="featured_products.length > 0">
                                <div v-for="(product,index) in pageOfProducts" :key="product.id" class="col-12 col-md-6 col-lg-6 col-xl-3 mb-product">
                                    <div class="card item h-100">
                                        <div v-if="product.image" class="thumbnail" :style="{'background-image': 'url('+product.image[0].url+')'}">
                                            <template>
                                                <span>
                                                    <img :src="product.image[0].url"
                                                    class="card-img-top"
                                                    :alt="product.name">
                                                </span>
                                            </template>
                                        </div>
                                        <div v-else class="thumbnail" :style="{'background-image': 'url(/hitpay/images/product.jpg)'}">
                                            <template>
                                                <span>
                                                    <img src="/hitpay/images/product.jpg"
                                                    class="card-img-top"
                                                    alt="product">
                                                </span>
                                            </template>
                                        </div>
                                        <div class="information flex-grow-1">
                                            <h5 class="title-product">{{ product.name }}</h5>
                                        </div>
                                        <div class="meta-bottom">
                                            <p class="price">{{ product.showPrice }}</p>
                                            <button class="btn btn-sm btn-primary btn-block" @click="addFeaturedProduct(product.id)" :disabled="product.is_manageable && !product.has_variations && (!product.quantity || !(product.quantity > 0))">
                                                <img src="/icons/ico-plus.svg" class="icon-plus"> Add
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="pagination-shop clearfix">
                                    <jw-pagination :items="featured_products" :maxPages=4 :pageSize=8 @changePage="onChangePage"></jw-pagination>
                                </div>
                            </div>
                            <div class="row featured-product" v-else>
                                <div class="empty-product text-center">
                                    <p class="icon"><img src="/images/ico-empty-data.svg" alt="empty"/></p>
                                    <p class="excerpt">You don't have any products added yet</p>
                                    <p><a class="btn btn-primary" :href="productLink()"><img src="/icons/ico-plus.svg" class="icon-plus"> Add product</a></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-12 col-md-12 col-lg-4">
                <div class="card sidebar round-md-top-0 shadow-sm border-0 mb-3">
                    <template v-if="terminals.location_id !== null">
                        <div class="card-body border-bottom p-4 d-block d-sm-flex justify-content-sm-between">
                            <div class="align-self-sm-center">
                                <label class="small text-uppercase text-muted mr-sm-3 mb-2 mb-sm-0">Terminal</label>
                            </div>
                            <div class="align-self-sm-center text-sm-right">
                                <div v-if="terminals.connected" class="media">
                                    <div class="media-body">
                                        <h6 class="font-weight-bold mt-0 mb-1">{{ terminals.connected.label }}</h6>
                                        <p class="small text-muted mb-0">Serial Number: {{ terminals.connected.serial_number }}<br>
                                            <a class="text-danger font-weight-bold" href="#" @click.prevent="disconnectTerminal(true)">Disconnect</a>
                                        </p>
                                    </div>
                                    <img :src="getDomain('icons/reader/reader-image-small.png')" class="align-self-center ml-3" alt="Terminal" height="48">
                                </div>
                                <button v-else class="btn btn-success btn-sm" @click="openDiscoverTerminalsModal()">Connect Terminal</button>
                            </div>
                        </div>
                    </template>
                    <div class="card-body border-bottom p-4">
                        <div class="align-self-sm-center">
                            <p class="text-uppercase title">Customer</p>
                        </div>
                        <div class="align-self-sm-center" v-if="!is_show_search_customer">
                            <button class="btn btn-secondary btn-block" @click="tryToAddCustomer">
                                <img src="/icons/ico-plus.svg" class="icon-plus"> Add customer
                            </button>
                        </div>
                        <div class="customer-search">
                            <div class="align-self-sm-center" v-if="is_show_search_customer">
                                <input v-model="customer.keywords" id="customersearch" type="text" class="form-control" placeholder="Enter name or email" aria-label="Customer Name or Email" @keyup="searchCustomer">
                            </div>
                            <div class="search-result customer-search-result shadow-sm rounded" v-if="customer.is_show_popup" >
                                <template v-if="customer.search_results.length > 0">
                                    <div v-for="(customer, index) in customer.search_results" class="item">
                                        <div class="information">
                                            <template v-if="customer.name">
                                                <p class="name font-weight-bold">{{ customer.name }}</p>
                                                <p class="small text-muted mb-0">{{ customer.email }}</p>
                                            </template>
                                            <template v-else>
                                                {{ customer.email }}
                                            </template>
                                            <p v-if="customer.phone_number" class="small text-muted mb-0">{{ customer.phone_number }}</p>
                                        </div>
                                        <button class="btn btn-sm btn-primary" @click="addCustomer(customer.id)"><img src="/icons/ico-plus.svg" class="icon-plus"> Add</button>
                                    </div>
                                </template>
                                <template v-else>
                                    <div class="modal-body">
                                        <div class="empty-result">
                                            <i v-if="customer.search_state === 'initial'" class="fa fas fa-users fa-3x"></i>
                                            <p v-else-if="customer.search_state === 'searching'" class="text-muted font-italic mb-0"><i class="fas fa-spin fa-spinner"></i></p>
                                            <div v-else>
                                                <p class="text">Customer doesn't exist</p>
                                                <a class="btn btn-sm btn-primary" :href="customerLink()"> <img src="/icons/ico-plus.svg" class="icon-plus"> Add Customer</a>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <div class="customer-information">
                            <template v-if="order_details.customer">
                                <div class="inner">
                                    <template v-if="order_details.customer.name">
                                        <p class="name">{{ order_details.customer.name }}</p>
                                        <p class="small text-muted mb-0">{{ order_details.customer.email }}</p>
                                    </template>
                                    <template v-else>
                                        {{ order_details.customer.email }}
                                    </template>
                                </div>
                                <a href="#" class="small text-danger" @click.prevent="removeCustomer">Remove</a>
                            </template>

                        </div>
                    </div>
                    <div class="card-body border-bottom p-4 d-flex justify-content-between">
                        <div class="align-self-center">
                            <label class="small text-uppercase text-muted mr-3 mr-lg-0 mb-0">Products</label>
                        </div>
                        <div class="align-self-center text-right d-lg-none">
                            <button class="btn btn-secondary btn-sm " @click="openSearchProductModel"><i class="fas fa-plus mr-2"></i> Product</button>
                        </div>
                    </div>
                    <div v-if="order_details.products.length > 0" class="order-products bg-light">
                        <div v-for="(product, index) in order_details.products" class="item clearfix">
                            <a class="small text-danger btn-close" href="#" @click.prevent="removeProduct(product.id)"><img src="/images/delete_icon.svg" alt="delete"></a>
                            <div class="thumbnail">
                                <div v-if='product.image'>
                                    <img :src="product.image" class="card-img-top border-bottom3" :alt="product.name">
                                </div>
                                <div v-else>
                                    <img src="/hitpay/images/product.jpg" class="card-img-top border-bottom3" :alt="product.name">
                                </div>
                            </div>
                            <div class="information">
                                <p class="title-product mb-0">{{ product.name }} <span class="small text-muted"></span>
                                </p>
                                <ul class="list-unstyled small mb-0" v-if="product.variation_key_1 || product.variation_key_2 || product.variation_key_3">
                                    <li v-if="product.variation_key_1">{{ product.variation_key_1 }}:
                                        <span class="text-muted">{{ product.variation_value_1 }}</span></li>
                                    <li v-if="product.variation_key_2">{{ product.variation_key_2 }}:
                                        <span class="text-muted">{{ product.variation_value_2 }}</span></li>
                                    <li v-if="product.variation_key_3">{{ product.variation_key_3 }}:
                                        <span class="text-muted">{{ product.variation_value_3 }}</span></li>
                                </ul>
                                <p class="small mb-0">{{ currency_display }}{{ Number(product.unit_price).toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,') }}
                                </p>
                                <p>
                                    <div class="cart-number clearfix">
                                        <span class="btn-decrease" @click="decreaseQuantity(index)">-</span>
                                        <input type="text" class="number" min="1" v-model="product.quantity"
                                               oninput="this.value = Math.abs(this.value)" @input="enterQuantity(index)">
                                        <span class="btn-increase" @click="increaseQuantity(index)">+</span>
                                    </div>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="order_details.products.length <= 0" class="card-body bg-light border-bottom p-4">
                        <p class="small text-muted font-italic mb-0">No product has been added.</p>
                    </div>
                    <div class="card-body frm-discount border-bottom p-4">
                        <div class="ctn-inner">
                            <div class="align-self-center">
                                <p class="text-uppercase title">Discount</p>
                            </div>
                               <div class="align-self-center text-right" v-if="!is_show_discount">
                                <button class="btn btn-secondary btn-block" @click="addDiscountModal" :disabled="order_details.products.length <= 0">
                                    <img src="/icons/ico-plus.svg" class="icon-plus"> Add discount
                                </button>
                            </div>
                            <div class="discount" v-if="is_show_discount">
                                <a class="small text-danger btn-close" href="#" @click.prevent="removeDiscount"><img src="/images/delete_icon.svg" alt="delete"></a>
                                <div class="name">
                                    <input type="text" v-model="discount.name" id="discount_search" class="form-control" placeholder="Name" aria-label="Discount name">
                                </div>
                                <div class="search-result shadow-sm rounded" v-if="discount.is_show_popup">
                                    <template v-if="discount.search_results.length > 0">
                                        <div v-for="(discount, index) in discount.search_results" class="item">
                                            <div class="information">
                                                <template v-if="discount.name">
                                                    <p class="name title-product">{{ discount.name }}</p>
                                                </template>
                                                <template v-if="discount.fixed_amount > 0">
                                                    {{ discount.fixed_amount }}
                                                </template>
                                                <template v-if="discount.percentage > 0">
                                                    <p class="amount">{{ discount.percentage *100 }} %</p>
                                                </template>
                                            </div>
                                            <button class="btn btn-sm btn-primary" @click="addDiscount(discount.id)"><img src="/icons/ico-plus.svg" class="icon-plus"> Add</button>
                                        </div>
                                    </template>
                                </div>
                                <div class="input-group mb-3">
                                    <input id="amount" v-model="discount.amount" class="form-control border-right-0 text-monospace" placeholder="Discount" aria-label="Discount amount" aria-describedby="amountLabel" @input="setDiscountAmount" :disabled="updating_discount" :class="{'is-invalid' : discount.error}">
                                    <div class="input-group-prepend">
                                        <div class="input-group-text rounded-right text-monospace" id="amountLabel">{{ currency_display }}</div>
                                    </div>
                                </div>
                                <span class="valid-error" role="alert" v-if="discount.error">{{ discount.error }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-if="tax_settings.length > 0" class="card-body border-bottom p-4 d-flex justify-content-between">
                        <div class="align-self-center">
                            <label class="small text-uppercase text-muted mr-3 mb-0">Tax Setting</label>
                            <p v-if="order_details.tax_setting_amount" class="mb-0">{{ order_details.tax_setting_name }}</p>
                        </div>
                        <div class="align-self-center text-right">
                            <template v-if="order_details.tax_setting_amount">
                                {{ Number(order_details.tax_setting_amount).toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,') }}%
                                <a href="#" @click="addTaxModal">Edit</a>
                            </template>
                            <button v-else class="btn btn-secondary btn-sm" @click="addTaxModal">
                                <i class="fas fa-plus mr-2"></i> Tax Setting
                            </button>
                        </div>
                    </div>
                    <div class="card-body border-bottom p-4 d-flex justify-content-between">
                        <div class="align-self-center">
                            <label class="font-weight-bold text-uppercase mr-3 mb-0">Total</label>
                        </div>
                        <div class="align-self-center text-right font-weight-bold">
                            {{ currency_display }}{{ Number(order_details.total_amount).toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,') }}
                        </div>
                    </div>
                </div>
                <button class="btn btn-primary btn-lg btn-block shadow-sm" :disabled="order_details.products.length <= 0 || is_processing" @click="checkout">
                    <template v-if="order.is_checking_out">
                        <i class="fas fa-spinner fa-spin"></i> Checking out…
                    </template>
                    <template v-else>
                        Checkout
                    </template>
                </button>
                <div v-if="general_error" class="mt-3">
                    {{ general_error }}
                </div>
            </div>
        </div>
        <div id="connectTerminalModal" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Terminals</h5>
                    </div>
                    <div v-if="terminals.is_discovering" class="modal-body bg-light text-center py-5 border-bottom">
                        <i class="fas fa-spinner fa-3x fa-spin text-primary"></i>
                    </div>
                    <template v-else>
                        <template v-if="terminals.discovered.length > 0">
                            <div v-for="(terminal, index) in terminals.discovered" class="modal-body bg-light border-bottom">
                                <div class="media">
                                    <img :src="getDomain('icons/reader/reader-image-small.png', 'dashboard')" class="align-self-center mr-3" alt="Terminal" height="48">
                                    <div class="media-body">
                                        <button v-if="terminals.connected && terminals.connected.id === terminal.id" class="btn btn-primary btn-sm float-right" @click="disconnectTerminal">Disconnect</button>
                                        <button v-else-if="terminal.status === 'online'" class="btn btn-primary btn-sm float-right" :disabled="terminals.connected" @click="connectTerminal(index)">Connect</button>
                                        <span v-else class="small badge badge-danger float-right">Offline</span>
                                        <h6 class="font-weight-bold mt-0">{{ terminal.label }}</h6>
                                        <p class="small text-muted mb-0">IP Address: {{ terminal.ip_address}}</p>
                                        <p class="small text-muted mb-0">Serial Number: {{ terminal.serial_number }}</p>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div v-else class="modal-body bg-light border-bottom">
                            {{ terminals.message }}
                        </div>
                    </template>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-light text-secondary" @click="discoverTerminals" :disabled="terminals.is_discovering">Scan Again</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <div id="askForPaymentMethods" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <template v-if="charges.chosen_method === 'card_present'">
                                Scan Card using Reader
                            </template>
                            <template v-else-if="charges.chosen_method === 'card'">
                                Enter Card Details
                            </template>
                            <template v-else-if="charges.chosen_method === 'alipay' || charges.chosen_method === 'grabpay'">
                                Complete The Payment
                            </template>
                            <template v-else-if="charges.chosen_method === 'paynow_online'">
                                Scan The QR Code
                            </template>
                            <template v-else-if="charges.chosen_method === 'cash'">
                                Succeeded
                            </template>
                            <template v-else-if="charges.chosen_method === 'link'">
                                Succeeded
                            </template>
                            <template v-else>
                                Select A Payment Method
                            </template>
                        </h5>
                    </div>
                    <div v-if="charges.charge_status === 'succeeded'" class="modal-body bg-light text-center">
                        <h3 class="mb-3">Done!</h3>
                        <p><i class="fas fa-check-circle fa-3x text-success"></i></p>
                        <p>Payment successful.</p>
                        <p class="h3 mb-3">{{ currency_display }}{{ Number(amount).toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,') }}</p>
                        <div v-if="is_receipt_sent">
                            Receipt has been sent to {{ recipient }}!
                        </div>
                        <div v-else class="input-group">
                            <input v-model="recipient" type="text" class="form-control" :class="{
                                'is-invalid': recipient_error,
                            }" placeholder="Recipient's Email" aria-label="Recipient's Email" :disabled="is_sending_receipt">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-primary" @click="sendReceipt" :disabled="is_sending_receipt">
                                    <i class="fas fa-envelope"></i>
                                    <i v-if="is_sending_receipt" class="ml-2 fas fa-spinner fa-spin"></i>
                                </button>
                            </div>
                        </div>
                        <span v-if="recipient_error" class="text-danger small mt-1" role="alert">{{ recipient_error }}</span>
                    </div>
                    <div v-else-if="charges.charge_status === 'link_succeeded'" class="modal-body bg-light text-center">
                        <h3 class="mb-3">Done!</h3>
                        <p><i class="fas fa-check-circle fa-3x text-success"></i></p>
                        <p>Payment link created.</p>
                        <p class="h3 mb-3">{{ currency_display }}{{ Number(amount).toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,') }}</p>
                        <div class="input-group">
                            <input type="hidden" id="payment_link" :value="charges.payment_link">
                            <b class="mb-2"><a :href="charges.payment_link">{{ charges.payment_link }}</a></b>
                            <button v-if="!charges.is_payment_link_copied" class="btn btn-primary btn-block" @click="copy">Copy</button>
                            <button v-if="charges.is_payment_link_copied" class="btn btn-primary btn-block" @click="copy">Copied!</button>
                        </div>
                        <span v-if="recipient_error" class="text-danger small mt-1" role="alert">{{ recipient_error }}</span>
                    </div>
                    <div v-else-if="charges.charge_status !== null" class="modal-body bg-light text-center">
                        <h3 class="mb-3">Whoops!</h3>
                        <p><i class="fas fa-exclamation-circle fa-3x text-danger"></i></p>
                        <p>{{ charges.charge_status }}</p>
                        <button class="btn btn-primary btn-sm" @click="tryAnotherMethod">Try another method?</button>
                    </div>
                    <div v-else-if="charges.chosen_method !== null" class="modal-body bg-light text-center">
                        <template v-if="charges.chosen_method === 'card_present'">
                            <h3 class="mb-3">Card Reader</h3>
                            <p>{{ currency_display }}{{ Number(amount).toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,') }}</p>
                            <template v-if="terminals.status === 'paying'">
                                <p><i class="fas fa-spinner fa-spin fa-3x text-primary"></i></p>
                                <p class="mb-0">Processing payment…</p>
                            </template>
                            <template v-else>
                                <p><i class="fas fa-spinner fa-spin fa-3x text-primary"></i></p>
                                <p>Awaiting customer to present card.</p>
                                <p class="mb-0">Continue payment by following the instruction on the reader.</p>
                            </template>
                        </template>
                        <template v-else-if="charges.chosen_method === 'paynow_online'">
                            <h3 class="mb-3">PayNow</h3>
                            <p>{{ currency_display }}{{ Number(amount).toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,') }}</p>
                            <div class="mb-3">
                                <div id="paynow_online-qr-code"></div>
                            </div>
                            <p class="mb-0">Awaiting payment. Please do not close this window until you receive payment confirmation.</p>
                        </template>
                        <template v-else-if="charges.chosen_method === 'grabpay'">
                            <h3 class="mb-3">GrabPay</h3>
                            <p>{{ currency_display }}{{ Number(amount).toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,') }}</p>
                            <div class="mb-3">
                                <div id="grabpay-qr-code"></div>
                            </div>
                            <p class="mb-0">Awaiting payment. Please do not close this window until you receive payment confirmation.</p>
                        </template>
                        <template v-else-if="charges.chosen_method === 'card'">
                            <template v-if="cards.status === 'paying'">
                                <p><i class="fas fa-spinner fa-spin fa-3x text-primary"></i></p>
                                <p class="mb-0">Processing payment…</p>
                            </template>
                            <template v-else-if="cards.status === null">
                                <h3 class="mb-3">Charge Card</h3>
                                <p class="h2 mb-3">{{ currency_display }}{{ Number(amount).toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,') }}</p>
                                <div v-if="cards.error" class="text-danger mb-1">{{ cards.error }}</div>
                                <div id="card-element" class="form-control bg-white p-2 mb-3"></div>
                                <button class="btn btn-primary btn-block" @click="manualCharge" :disabled="cards.is_charging">
                                    Charge {{ currency_display }}{{ Number(amount).toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,') }}
                                    <i v-if="cards.is_charging" class="fas fa-spinner fa-spin"></i>
                                </button>
                            </template>
                        </template>
                        <template v-else-if="charges.chosen_method === 'alipay' || charges.chosen_method === 'grabpay'">
                            <h3 class="mb-3">Alipay</h3>
                            <p>{{ currency_display }}{{Number(amount).toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,') }}</p>
                            <p><i class="fas fa-spinner fa-spin fa-3x text-primary"></i></p>
                            <p class="mb-0">Awaiting payment. Please do not close this window until you receive payment confirmation.</p>
                        </template>
                        <template v-else-if="charges.chosen_method === 'cash'">
                            <h3 class="mb-3">Cash</h3>
                            <p>{{ currency_display }}{{ Number(amount).toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,') }}</p>
                            <p>Are you sure you want to log a cash charge?</p>
                            <button class="btn btn-primary btn-block" @click="logCash">Confirm</button>
                        </template>
                        <template v-else-if="charges.chosen_method === 'link'">
                            <h3 class="mb-3">Payment Link</h3>
                            <p>{{ currency_display }}{{ Number(amount).toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,') }}</p>
                            <p>Are you sure you want to create a payment link?</p>
                            <button class="btn btn-primary btn-block" @click="logLink">Confirm</button>
                        </template>
                        <template v-else>
                            Somethingwrong
                        </template>
                    </div>
                    <template v-else>
                        <div v-if="charges.methods.card_present && terminals.connected" class="modal-body bg-light border-bottom">
                            <div class="media">
                                    <img :src="getDomain('icons/payment-methods/pos.svg')" class="listing align-self-center mr-3" alt="Card Reader">
                                    <div class="media-body align-self-center">
                                        <h6 class="font-weight-bold mt-0 mb-1">Card Reader</h6>
                                        <p class="small text-muted mb-0">Swipe, dip or tap card</p>
                                    </div>
                                    <button class="btn btn-danger btn-sm align-self-center ml-3" @click="useMethod('card_present')">Select</button>
                                </div>
                            </div>
                            <div v-if="charges.methods.paynow_online" class="modal-body bg-light border-bottom">
                                <div class="media">
                                    <img :src="getDomain('icons/payment-methods/paynow.jpg')" class="listing align-self-center mr-3" alt="PayNow">
                                    <div class="media-body align-self-center">
                                        <h6 class="font-weight-bold mt-0 mb-1">PayNow</h6>
                                        <p class="small text-muted mb-0">Scan to pay</p>
                                    </div>
                                    <button class="btn btn-danger btn-sm align-self-center ml-3" @click="useMethod('paynow_online')">Select</button>
                                </div>
                            </div>
                            <div v-if="charges.methods.grabpay" class="modal-body bg-light border-bottom">
                                <div class="media">
                                    <img :src="getDomain('icons/payment-methods/grabpay.png')" class="listing align-self-center mr-3" alt="GrabPay">
                                    <div class="media-body align-self-center">
                                        <h6 class="font-weight-bold mt-0 mb-1">GrabPay</h6>
                                    </div>
                                    <button class="btn btn-danger btn-sm align-self-center ml-3" @click="useMethod('grabpay')">Select</button>
                                </div>
                            </div>
                            <div v-if="charges.methods.card" class="modal-body bg-light border-bottom">
                                <div class="media">
                                    <img :src="getDomain('icons/payment-methods/card.svg')" class="listing align-self-center mr-3" alt="Card">
                                    <div class="media-body align-self-center">
                                        <h6 class="font-weight-bold mt-0 mb-1">Charge Card</h6>
                                        <p class="small text-muted mb-0">Enter card details</p>
                                    </div>
                                    <button class="btn btn-danger btn-sm align-self-center ml-3" @click="useMethod('card')">Select</button>
                                </div>
                            </div>
                            <div v-if="charges.methods.alipay" class="modal-body bg-light border-bottom">
                                <div class="media">
                                    <img :src="getDomain('icons/payment-methods/alipay.svg')" class="listing align-self-center mr-3" alt="Alipay">
                                    <div class="media-body align-self-center">
                                        <h6 class="font-weight-bold mt-0 mb-1">Alipay</h6>
                                        <p class="small text-muted mb-0">Scan to pay or authenticate using Alipay login details</p>
                                    </div>
                                    <button class="btn btn-danger btn-sm align-self-center ml-3" @click="useMethod('alipay')">Select</button>
                                </div>
                            </div>
                            <div class="modal-body bg-light border-bottom">
                                <div class="media">
                                    <img :src="getDomain('icons/payment-methods/cash.svg')" class="listing align-self-center mr-3" alt="Cash">
                                    <div class="media-body align-self-center">
                                        <h6 class="font-weight-bold mt-0 mb-1">Cash</h6>
                                        <p class="small text-muted mb-0">Record cash transaction</p>
                                    </div>
                                    <button class="btn btn-danger btn-sm align-self-center ml-3" @click="useMethod('cash')">Select</button>
                                </div>
                            </div>
                            <div class="modal-body bg-light border-bottom">
                                <div class="media">
                                    <img :src="getDomain('icons/payment-methods/weblink.png', 'dashboard')" class="listing align-self-center mr-3" alt="Payment Link">
                                    <div class="media-body align-self-center">
                                        <h6 class="font-weight-bold mt-0 mb-1">Payment Link</h6>
                                        <label for="allow_repeated_payments" class="small text-muted mb-0">Allow multiple payments</label>
                                        <input id="allow_repeated_payments" type="checkbox" v-model="charges.allow_repeated_payments">
                                    </div>
                                    <button class="btn btn-danger btn-sm align-self-center ml-3" @click="useMethod('link')">Select</button>
                                </div>
                            </div>
                    </template>
                    <div class="modal-footer">
                        <a data-dismiss="modal" href="#">{{ charges.charge_status === 'succeeded' ? 'Close' : 'Cancel' }}</a>
                    </div>
                </div>
            </div>
        </div>
        <div id="searchProductModel" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-body border-bottom">
                        <h5 class="modal-title mb-3">
                            Search Product
                        </h5>
                        <div class="input-group">
                            <input type="text" v-model="order.keywords" class="form-control bg-light" placeholder="Product Name" aria-label="Product Name" :disabled="is_searching_product">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-primary" @click="searchProduct" :disabled="is_searching_product">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <template v-if="is_searching_product">
                        <div class="modal-body bg-light border-bottom">
                            <div class="text-center p-4">
                                <i class="fa fas fa-spinner fa-spin fa-3x"></i>
                            </div>
                        </div>
                    </template>
                    <template v-else-if="order.search_results.length > 0">
                        <div v-for="(product, index) in order.search_results" class="modal-body bg-light border-bottom">
                            <template v-if="product.variations.length > 1">
                                <p class="font-weight-bold mb-2">{{ product.name }}</p>
                                <template v-for="(variation, index) in product.variations">
                                    <div class="py-2" :class="{
                                        'border-top': product.variations.length !== index,
                                    }">
                                        <button v-if="(product.is_manageable && variation.quantity > 0 || !product.is_manageable)" class="btn btn-sm btn-success float-right" @click="addProduct(variation.id, true)">add</button>
                                        <p v-else class="small text-danger float-right">Unavailable</p>
                                        <ul class="list-unstyled small mb-0" v-if="product.variation_key_1 || product.variation_key_2 || product.variation_key_3">
                                            <li v-if="product.variation_key_1">{{ product.variation_key_1 }}:
                                                <span class="text-muted">{{ variation.variation_value_1 }}</span>
                                            </li>
                                            <li v-if="product.variation_key_2">{{ product.variation_key_2 }}:
                                                <span class="text-muted">{{ variation.variation_value_2 }}</span>
                                            </li>
                                            <li v-if="product.variation_key_3">{{ product.variation_key_3 }}:
                                                <span class="text-muted">{{ variation.variation_value_3 }}</span>
                                            </li>
                                        </ul>
                                        <p v-if="product.is_manageable" class="small mb-1">Quantity Available: {{ variation.quantity }}</p>
                                        <p class="small mb-0">Price:
                                            <span class="text-muted">{{ currency_display }}{{ Number(variation.price).toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,') }}</span>
                                        </p>
                                    </div>
                                </template>
                            </template>
                            <template v-else>
                                <button v-if="(product.is_manageable && product.variations[0].quantity > 0 || !product.is_manageable)" class="btn btn-sm btn-success float-right" @click="addProduct(product.variations[0].id, true)">add</button>
                                <p v-else class="small text-danger float-right">Unavailable</p>
                                <p class="font-weight-bold mb-0">{{ product.name }}</p>
                                <p v-if="product.variations[0].description" class="small mb-1">{{ product.variations[0].description }}</p>
                                <p v-if="product.is_manageable" class="small mb-1">Quantity Available: {{ product.variations[0].quantity }}</p>
                                <p class="small mb-0">Price:
                                    <span class="text-muted">{{ currency_display }}{{ Number(product.variations[0].price).toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,') }}</span>
                                </p>
                            </template>
                        </div>
                    </template>
                    <template v-else>
                        <div class="modal-body bg-light border-bottom">
                            <div class="text-center p-4">
                                <i class="fa fas fa-boxes fa-3x"></i>
                            </div>
                        </div>
                    </template>
                    <div class="modal-footer border-top-0">
                        <a data-dismiss="modal" href="#">Close</a>
                    </div>
                </div>
            </div>
        </div>
        <div id="searchCustomerModel" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-body">
                        <h5 class="modal-title mb-0">
                            Search Customer
                        </h5>
                    </div>
                    <input v-model="customer.keywords" type="text" class="form-control border-left-0 border-right-0 rounded-0 bg-light" placeholder="Enter Customer Name or Email" aria-label="Customer Name or Email" @keyup="searchCustomer">
                    <template v-if="customer.search_results.length > 0">
                        <div v-for="(customer, index) in customer.search_results" class="modal-body" :class="{
                            'border-top': index !== 0,
                        }">
                            <button class="btn btn-sm btn-primary float-right" @click="addCustomer(customer.id)">Add</button>
                            <template v-if="customer.name">
                                {{ customer.name }}
                                <p class="small text-muted mb-0">Email: {{ customer.email }}</p>
                            </template>
                            <template v-else>
                                {{ customer.email }}
                            </template>
                            <p v-if="customer.phone_number" class="small text-muted mb-0">Phone Number: {{ customer.phone_number }}</p>
                            <p v-if="customer.address" class="small text-muted mb-0">Address: {{ customer.address }}</p>
                        </div>
                    </template>
                    <template v-else>
                        <div class="modal-body bg-light">
                            <div class="text-center p-4">
                                <i v-if="customer.search_state === 'initial'" class="fa fas fa-users fa-3x"></i>
                                <p v-else-if="customer.search_state === 'searching'" class="text-muted font-italic mb-0"><i class="fas fa-spin fa-spinner"></i></p>
                                <a v-else class="btn btn-sm btn-primary" :href="customerLink()">Add Customer</a>
                            </div>
                        </div>
                    </template>
                    <div class="modal-footer border-top">
                        <a data-dismiss="modal" href="#">Close</a>
                    </div>
                </div>
            </div>
        </div>
        <div id="addFeaturedProductModel" class="modal modal-featured-product" tabindex="-1" role="dialog" data-backdrop="static">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-footer border-top-0">
                        <a data-dismiss="modal" href="#" class="close"><img src="/images/delete_icon.svg" alt="delete"></a>
                    </div>
                    <div v-if="product.selected_product" class="modal-body">
                        <div class="row">
                            <div class="col-12 col-sm-4 col-md-4 col-lg-4">
                                <div class="thumbnail">
                                    <div v-if="product.selected_product.image">
                                        <img :src="product.selected_product.image[0].url">
                                    </div>
                                    <div v-if="!product.selected_product.image">
                                        <img src="/hitpay/images/product.jpg">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-8 col-md-8 col-8">
                                <div class="information">
                                    <p class="modal-product-title">{{product.selected_product.name}}</p>
                                    <div class="radio-toolbar clearfix" ref="variations">
                                        <template v-for="(variation,key) in product.selected_product.variations">
                                            <template v-if="(product.selected_product.is_manageable && variation.quantity > 0) || !product.selected_product.is_manageable">
                                                <input type="radio" :id="variation.id" :name="'variation' + product.selected_product.id + key" v-model="product.selected_variation_id"
                                                       :value="variation.id" :checked="key===0">
                                                <label :for="variation.id"><span>{{variation.description}}</span></label>
                                            </template>
                                        </template>
                                    </div>
                                    <p class="price">{{ currency_display }}{{ Number(product.selected_variation_price).toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,') }}
                                    </p>
                                </div>
                                <button class="btn btn-primary" @click="addFeaturedProductVariation()">
                                    <img src="/icons/ico-plus.svg" class="icon-plus"> Add
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="updateProductQuantityModel" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-body border-bottom">
                        <h5 class="modal-title mb-0">
                            Update {{ to_be_updated.name }}
                        </h5>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="toBeUpdatedQuantity" class="text-uppercast text-muted small">Quantity</label>
                            <input id="toBeUpdatedQuantity" v-model="to_be_updated.quantity" class="form-control bg-light" type="number" :disabled="updating_quantity">
                        </div>
                        <div class="form-group">
                            <label for="toBeUpdatedRemark" class="text-uppercast text-muted small">Remark</label>
                            <textarea id="toBeUpdatedRemark" v-model="to_be_updated.remark" class="form-control bg-light" :disabled="updating_quantity"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button class="btn btn-text" data-dismiss="modal" href="#">Close</button>
                        <button class="btn btn-primary" @click.prevent="updateProduct(to_be_updated.id)" :disabled="updating_quantity">Update
                            <i v-if="updating_quantity" class="fas fa-spinner fa-spin"></i></button>
                    </div>
                </div>
            </div>
        </div>
        <div id="setDiscountModel" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-body border-bottom">
                        <h5 class="modal-title mb-0">
                            Set Discount
                        </h5>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="toBeSetDiscountName" class="text-uppercast text-muted small">Name</label>
                            <input id="toBeSetDiscountName" v-model="discount.name" class="form-control bg-light" :disabled="updating_discount" maxlength="32">
                        </div>
                        <div class="form-group">
                            <label for="toBeSetDiscount" class="text-uppercast text-muted small">Discount</label>
                            <input id="toBeSetDiscount" v-model="discount.amount" class="form-control bg-light" type="number" :disabled="updating_discount">
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button class="btn btn-text" data-dismiss="modal" href="#">Close</button>
                        <button class="btn btn-primary" @click.prevent="setDiscountAmount" :disabled="updating_discount">Update
                            <i v-if="updating_discount" class="fas fa-spinner fa-spin"></i></button>
                    </div>
                </div>
            </div>
        </div>
        <div id="setTaxModel" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-body border-bottom">
                        <h5 class="modal-title mb-0">
                            Set Tax Setting
                        </h5>
                    </div>
                    <div class="modal-body">
                        <select id="tax_setting" class="custom-select"
                                v-model="tax_setting.id"
                                :disabled="is_processing">
                            <option value="" disabled>Select Tax Setting</option>
                            <option v-for="tax in tax_settings" :value="tax.id">
                                {{ tax.name }}
                            </option>
                        </select>
                    </div>
                    <div class="modal-footer border-top">
                        <button class="btn btn-text" data-dismiss="modal" href="#">Close</button>
                        <button class="btn btn-primary" @click.prevent="setTaxAmount" :disabled="updating_tax_setting">Update
                            <i v-if="updating_tax_setting" class="fas fa-spinner fa-spin"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import CardReader from './CardReader'

    export default {
        name: "PointOfSale",
        components: {
          CardReader
        },
        watch: {
            'product.selected_variation_id': {
                handler(selected_variation_id) {
                    if(this.product.selected_product == null)
                        return;

                    let variation = _.find(this.product.selected_product.variations, (variation) => {
                        return variation.id === selected_variation_id;
                    });

                    this.product.selected_variation_price = variation.price;
                }
            }
        },
        props: {
            tax_settings: {
                type: Array,
            },
            categories: {
                type: Array,
                required: true
            },
            featuredproducts: {
                type: Array
            },
            featured_product_attrs: {
                type: Object
            }
        },

        data() {
            return {
                page_type: 'point_of_sale',
                guides_type: [],
                is_guides_type: false,
                amount: '',
                quick_sale_remark: '',
                base_url: null,
                cards: {
                    elements: null,
                    error: null,
                    is_charging: false,
                    status: null,
                    stripe: null,
                },
                charges: {
                    charge_object: null,
                    chosen_method: null,
                    charge_status: null,
                    allow_repeated_payments: false,
                    payment_link: null,
                    is_payment_link_copied: false,
                    methods: {
                        alipay: false,
                        card: false,
                        card_present: false,
                        paynow_online: false,
                        grabpay: false,
                    },
                    modal: null,
                },
                collected_amount: '',
                currency: null,
                currency_display: 'unknown',
                current_tab: 'quick-sale-tab',
                existing_charge: null,
                general_error: null,
                is_processing: false,
                order_details: {
                    customer: null,
                    products: [],
                    tax_amount: 0,
                    discount_name: '',
                    discount_amount: 0,
                    total_amount: 0,
                    tax_setting_name: '',
                    tax_setting_amount: 0,
                },
                customer: {
                    keywords: '',
                    timeout: null,
                    modal: null,
                    search_results: [],
                    search_state: 'initial',
                    is_show_popup: false,
                },
                discount: {
                    name: '',
                    amount: 0,
                    modal: null,
                    error: '',
                    timeout: null,
                    search_state: 'initial',
                    keywords: '',
                    search_results: [],
                    is_show_popup: false,
                },
                tax_setting: {
                    id: '',
                    name: '',
                    amount: 0,
                    modal: null,
                },
                order: {
                    timeout: null,
                    is_checking_out: false,
                    keywords: '',
                    modal: null,
                    search_results: [],
                },
                product: {
                    modal: null,
                    selected_product: null,
                    selected_variation_id: 0,
                    selected_variation_price: 0,
                    is_show_popup: false,
                },
                recipient: null,
                recipient_error: null,
                is_sending_receipt: false,
                is_receipt_sent: false,
                is_searching_product: false,
                terminal_status: null,
                terminals: {
                    connected: null,
                    discovered: [],
                    is_discovering: true,
                    location_id: null,
                    message: null,
                    modal: null,
                    stripe: null,
                    status: null,
                },
                updating_quantity: false,
                updating_discount: false,
                updating_tax_setting: false,
                to_be_updated_modal: null,
                to_be_updated: {
                    name: null,
                    quantity: 0,
                },
                activeCategory: 'home',
                is_show_search_customer: false,
                is_show_discount: false,
                pageOfProducts: [],
                featured_products: [],
            };
        },

        mounted() {

            this.base_url = this.getDomain('business/' + Business.id + '/point-of-sale/', 'dashboard');

            this.charges.modal = $('#askForPaymentMethods');

            this.charges.modal.on('hidden.bs.modal', () => {
                if (this.charges.chosen_method === 'card_present') {
                    try {
                        this.terminals.status = null;
                        this.terminals.stripe.cancelCollectPaymentMethod();
                    } catch (e) {
                        //
                    }
                } else if (this.charges.charge_status !== 'succeeded' && this.existing_charge && this.existing_charge.charge_id) {
                    axios.delete(this.base_url + 'charge/' + this.existing_charge.charge_id);
                }

                this.amount = '';
                this.cards.card = null;
                this.cards.error = null;
                this.cards.is_charging = false;
                this.cards.status = null;
                this.cards.stripe = null;
                this.charges.charge_object = null;
                this.charges.charge_status = null;
                this.charges.chosen_method = null;
                this.charges.methods = {
                    alipay: false,
                    card: false,
                    card_present: false,
                    paynow_online: false,
                    grabpay: false,
                };
                this.existing_charge = null;
                this.is_processing = false;
                this.terminals.status = null;
                this.recipient = null;
                this.recipient_error = null;
                this.is_receipt_sent = false;
                this.is_sending_receipt = false;
                this.quick_sale_remark = '';
                this.order = {
                    timeout: null,
                    is_checking_out: false,
                    keywords: '',
                    modal: null,
                    search_results: [],
                };
            });

            if (Business.stripe_terminal_locations) {

                // TODO - 2020-02-20
                //
                // Usually we detect the only stripe terminal location here. But sometimes it will be returned in array
                // ACCIDENTALLY. So if that happened, we pick only the first stripe terminal location here. And we can
                // control also the first stripe terminal location returned from server and don't need the dashboard to
                // have select option.

                if (Business.stripe_terminal_locations.stripe_terminal_location_id) {
                    this.terminals.location_id = Business.stripe_terminal_locations.stripe_terminal_location_id;
                } else if (Business.stripe_terminal_locations.length > 0) {
                    let terminal_location = _.first(Business.stripe_terminal_locations);

                    if (terminal_location.stripe_terminal_location_id) {
                        this.terminals.location_id = terminal_location.stripe_terminal_location_id;
                    }
                }
            }

            let featuredProductsWithAttrs = [];
            let j = 0;
            _.each(this.featuredproducts, (featuredProduct) => {
                featuredProduct.imageSrc = this.featured_product_attrs['image'][j];
                featuredProduct.showPrice = this.featured_product_attrs['price'][j];
                featuredProduct.available = this.featured_product_attrs['available'][j];

                featuredProductsWithAttrs.push(featuredProduct);
                j++;
            });
            this.featured_products = featuredProductsWithAttrs;

            this.currency = Business.currency;

            this.currency_display = this.currency.toUpperCase();

            this.terminals.modal = $('#connectTerminalModal');

            this.terminals.modal.on('show.bs.modal', () => {
                this.discoverTerminals();
            });

            this.terminals.stripe = StripeTerminal.create({
                onFetchConnectionToken: async () => {
                    return axios.post(this.base_url + 'connection_token').then(({data}) => data.secret);
                },
                onUnexpectedReaderDisconnect: () => {
                    try {
                        this.terminals.stripe.disconnectReader();
                    } catch (e) {
                        //
                    }

                    // todo fix this, use modal to display
                    this.terminals.connected = null;
                    this.terminal_status = 'The reader is disconnected.';
                },
            });

            this.order.modal = $('#searchProductModel');

            this.order.modal.on('hidden.bs.modal', () => {
                this.order.keywords = '';
                this.order.search_results = [];
            });

            this.discount.modal = $('#setDiscountModel');

            this.discount.modal.on('hidden.bs.modal', () => {
                this.discount.name = 'Discount';
                this.discount.amount = 0;
                this.updating_discount = false;
            });

            this.tax_setting.modal = $('#setTaxModel');

            this.tax_setting.modal.on('hidden.bs.modal', () => {
                this.tax_setting.name = '';
                this.tax_setting.amount = 0;
                this.updating_tax_setting = false;
            });

            this.customer.modal = $('#searchCustomerModel');

            this.customer.modal.on('hidden.bs.modal', () => {
                this.customer.keywords = '';
                this.customer.search_results = [];
                this.customer.search_state = 'initial';
            });

            this.product.modal = $('#addFeaturedProductModel');
            this.product.modal.on('hidden.bs.modal', () => {
                this.product.selected_product = null;
                this.product.selected_variation_id = 0;
            });

            window.onbeforeunload = () => {
                this.clearTerminalCart();
                this.disconnectTerminal();
            }
        },

        methods: {
            appendNumber(value) {
                let indexOfPeriod = this.amount.indexOf('.');

                if (indexOfPeriod !== -1) {
                    if (value === '.') {
                        return;
                    } else if (this.amount.substr(indexOfPeriod).length > 2) {
                        return;
                    }
                }

                this.amount = this.amount + value;
            },

            backspaceNumber() {
                this.amount = this.amount.slice(0, -1);
            },

            validateAmountInput(event) {
                event.preventDefault();

                if (/^\d$/.test(event.key)) {
                    this.appendNumber(event.key)
                } else if (event.keyCode === 190) {
                    this.appendNumber('.')
                } else if (event.keyCode === 8 || event.keyCode === 46) {
                    this.backspaceNumber();
                }

                if(this.amount.length > 20) {
                    this.amount = this.amount.substr(0, 20);
                }
            },

            openDiscoverTerminalsModal() {
                this.terminals.modal.modal('show');
            },

            openSearchProductModel() {
                this.order.modal.modal('show');
            },

            openAskForPaymentMethods(data) {
                this.charges.charge_object = data.charge;

                _.forEach(data.payment_methods, (value) => {
                    this.charges.methods[value] = true;
                });

                this.charges.modal.modal('show');
            },

            useMethod(method) {
                this.charges.chosen_method = method;

                if (this.charges.chosen_method === 'cash' || this.charges.chosen_method === 'link') {
                    // complete
                } else {
                    return axios.post(this.base_url + 'charge/' + this.charges.charge_object.id + '/payment-intent', {
                        method: this.charges.chosen_method,
                        terminal_id: this.terminals.connected.serial_number
                    }).then(({data}) => {
                        this.existing_charge = data;

                        if (this.charges.chosen_method === 'card_present') {
                            this.terminals.stripe.collectPaymentMethod(data.payment_intent.client_secret)
                                .then(result => {
                                    if (result.error) {
                                        if (result.error.code !== 'canceled') {
                                            this.charges.charge_status = this.getTerminalErrorMessage(result.error);
                                        } else {
                                            console.log(result.error);
                                        }
                                    } else {
                                        this.terminals.status = 'paying';
                                        this.terminals.stripe.processPayment(result.paymentIntent).then(result => {
                                            if (result.error) {
                                                this.charges.charge_status = this.getTerminalErrorMessage(result.error);
                                            } else if (this.existing_charge && result.paymentIntent) {
                                                axios.post(this.base_url + 'payment-intent/' + this.existing_charge.id).then(({data}) => {
                                                    this.charges.charge_status = 'succeeded';
                                                    this.resetOrder();
                                                });
                                            }
                                        });
                                    }
                                });
                        } else {
                            if (this.charges.chosen_method === 'card') {
                                this.cards.stripe = Stripe(StripePublishableKey, {
                                    betas: [
                                        'payment_intent_beta_3',
                                    ],
                                });

                                this.cards.elements = this.cards.stripe.elements();
                                this.cards.card = this.cards.elements.create('card', {
                                    hidePostalCode: true,
                                    style: {
                                        base: {
                                            iconColor: '#4A50B5',
                                            color: '#495057',
                                            fontWeight: 400,
                                            fontFamily: 'Inter, Arial, sans-serif',
                                            fontSmoothing: 'antialiased',
                                            fontSize: '16px',
                                            '::placeholder': {
                                                color: '#cecece',
                                                fontWeight: 400,
                                                fontFamily: 'Inter, Arial, sans-serif',
                                                fontSize: '16px',
                                            },
                                        },
                                        invalid: {
                                            iconColor: '#dc3545',
                                            color: '#dc3545',
                                        },
                                    }
                                });

                                this.cards.card.mount('#card-element');
                                this.cards.card.on('change', ({error}) => {
                                    if (error) {
                                        this.cards.error = error.message;
                                    } else {
                                        this.cards.error = null;
                                    }
                                });
                            } else if (this.charges.chosen_method === 'paynow_online') {
                                if (data.paynow_online.qr_code_data === 'service_unavailable') {
                                    alert('PayNow QR is currently not available. Please use another payment method.')
                                }

                                new QRCode('paynow_online-qr-code', {
                                    text: data.paynow_online.qr_code_data,
                                    width: 256,
                                    height: 256,
                                    colorDark: '#840070',
                                    colorLight: '#fff',
                                    correctLevel: QRCode.CorrectLevel.H,
                                });

                                this.pollChargeStatus();
                            } else if (this.charges.chosen_method === 'grabpay') {
                                this.cards.stripe = Stripe(StripePublishableKey, {
                                    betas: [
                                        'grabpay_pm_beta_1',
                                    ],
                                });

                                this.cards.stripe.confirmGrabPayPayment(data.payment_intent.client_secret, {
                                    return_url: this.getDomain('close', 'dashboard'),
                                }, {
                                    handleActions: false,
                                }).then((response) => {
                                    window.open(response.paymentIntent.next_action.redirect_to_url.url, '_blank');
                                });

                                this.pollChargeStatus();
                            } else if (this.charges.chosen_method === 'alipay') {
                                window.open(data.alipay.redirect_url, '_blank');

                                this.pollChargeStatus();
                            }
                        }
                    }).catch(({response}) => {
                        if (response.status === 400) {
                            this.cards.error = response.data.error_message;
                        } else {
                            console.error(response);
                        }
                    });
                }
            },

            tryAnotherMethod() {
                this.charges.chosen_method = null;
                this.charges.charge_status = null;
                this.terminals.status = null;
            },

            async charge() {
                this.is_processing = true;
                this.general_error = null;

                if (isNaN(this.amount)) {
                     this.general_error = "The amount must be a number.";
                     this.is_processing = false;
                     return;
                }

                if (this.amount <= 0) {
                    this.general_error = "The amount can\'t have less than 0.";
                    this.is_processing = false;
                    return;
                }

                if( this.amount.length > 14) {
                    this.general_error = "The amount may not be greater than 14 characters.";
                    this.is_processing = false;
                    return;
                }

                if (this.current_tab === 'quick-sale-tab') {
                    axios.post(this.base_url + 'charge', {
                        remark: this.quick_sale_remark,
                        currency: this.currency,
                        amount: this.amount,
                    }).then(({data}) => {
                        this.openAskForPaymentMethods(data);
                    }).catch(({response}) => {
                        if (response.status === 422) {
                            this.is_processing = false;

                            _.forEach(response.data.errors, (value, key) => {
                                this.general_error = _.first(value);
                            });
                        }
                    });
                }
            },

            discoverTerminals() {
                this.terminals.discovered = [];
                this.terminals.is_discovering = true;
                this.terminals.message = null;

                this.terminals.stripe.discoverReaders({
                    simulated: false, // todo test
                    location: this.terminals.location_id,
                }).then((discoverResult) => {
                    this.terminals.is_discovering = false;

                    if (discoverResult.error) {
                        this.terminals.message = 'Failed to discover: ' + discoverResult.error;
                    } else if (discoverResult.discoveredReaders.length === 0) {
                        this.terminals.message = 'No available readers.';
                    } else {
                        this.terminals.discovered = discoverResult.discoveredReaders;
                    }
                });
            },

            connectTerminal(index) {
                this.terminals.is_discovering = true;

                if (this.terminals.discovered[index]) {
                    this.terminals.stripe.clearCachedCredentials();
                    this.terminals.stripe.connectReader(this.terminals.discovered[index], {
                        fail_if_in_use: false,
                    }).then((connectResult) => {
                        this.terminals.is_discovering = false;

                        if (connectResult.error) {
                            this.terminals.discovered = [];

                            console.log(connectResult.error);
                            this.terminals.message = 'Failed to connect: ' + connectResult.error;
                        } else {
                            this.terminals.connected = connectResult.reader;
                            this.terminals.modal.modal('hide');
                        }
                    });
                } else {
                    this.discoverTerminals();
                }
            },

            disconnectTerminal() {
                if (this.terminals.connected) {
                    this.terminals.stripe.disconnectReader();
                }

                this.terminals.connected = null;
            },

            cancelTerminalPayment() {
                //this.terminals.stripe.cancelCollectPaymentMethod();
            },

            getTerminalErrorMessage(data) {
                if (data.code === 'card_declined') {
                    switch (data.decline_code) {
                        case 'invalid_pin':
                            return 'You have entered an incorrect pin. Please charge payment again.';
                        case'withdrawal_count_limit_exceeded':
                            return 'The customer has exceeded the balance or credit limit available on their card. Please charge payment again with a different card or another payment method.';
                        case'pin_try_exceeded':
                            return 'The allowable number of PIN tries has been exceeded. Please charge payment again with a new card or another payment method.';
                        case'call_issuer':
                        case'generic_decline':
                            return 'Payment declined. Please try again';
                    }
                }

                return 'Payment declined. Please try again';
            },

            changeTab(tab) {
                this.current_tab = tab;
            },

            async pollChargeStatus(start = 0) {
                start++;

                if (this.existing_charge === null) {
                    return;
                }

                axios.get(this.base_url + 'payment-intent/' + this.existing_charge.id).then(({data}) => {
                    if (data.charge.status === 'succeeded') {
                        this.charges.charge_status = 'succeeded';
                        this.resetOrder();
                    } else if (data.status === 'failed') {
                        this.charges.charge_status = 'The payment was failed.';
                    } else if (data.status === 'canceled') {
                        this.charges.charge_status = 'The payment was canceled by the customer.';
                    } else if (start <= 300) {
                        setTimeout(this.pollChargeStatus, 1000, start);
                    } else {
                        console.warn(new Error('Polling timed out.'));
                    }
                });
            },

            logCash() {
                axios.post(this.base_url + 'charge/' + this.charges.charge_object.id + '/cash').then(({data}) => {
                    this.charges.charge_status = 'succeeded';
                    this.resetOrder();
                });
            },

            logLink() {
                axios.post(this.base_url + 'charge/' + this.charges.charge_object.id + '/link', {
                        allow_repeated_payments: this.charges.allow_repeated_payments
                    }).then(({data}) => {
                    this.charges.charge_status = 'link_succeeded';
                    this.charges.payment_link = data.payment_link;
                    this.resetOrder();
                });
            },

            async manualCharge() {
                if (this.cards.stripe === null || this.cards.card === null) {
                    return;
                }

                this.cards.error = null;
                this.cards.is_charging = true;

                await this.cards.stripe.createPaymentMethod({
                    type : "card",
                    card : this.cards.card
                }).then(async result => {
                    if (result.error) {
                        this.cards.error = result.error.message;
                        this.cards.is_charging = false;
                    } else {
                        var updatePaymentIntentUrl = `${this.base_url}payment-intent/${this.existing_charge.id}`;
                        var stripeCardElement = this.cards.elements.getElement("card");

                        await axios.put(updatePaymentIntentUrl, {
                            payment_method_id : result.paymentMethod.id,
                        }).then(async ({ data }) => {
                            if (data.status === "requires_source_action" || data.status === "requires_action") {
                                await this.cards.stripe.handleCardAction(data.payment_intent.client_secret).then(async result => {
                                    if (result.error) {
                                        this.cards.error = result.error.message;
                                        this.cards.is_charging = false;
                                    } else {
                                        await axios.put(updatePaymentIntentUrl).then(() => {
                                            stripeCardElement.unmount();

                                            this.charges.charge_status = "succeeded";
                                            this.resetOrder();
                                        }).catch((response) => {
                                            if (response.response.status === 400) {
                                                this.cards.error = response.response.data.error_message;
                                                this.cards.is_charging = false;
                                            } else {
                                                console.error(response);
                                            }
                                        });
                                    }
                                });
                            } else {

                                stripeCardElement.unmount();
                                this.charges.charge_status = "succeeded";
                                this.resetOrder();
                            }
                        }).catch((response) => {
                            if (response.response.status === 400) {
                                this.cards.error = response.response.data.error_message;
                                this.cards.is_charging = false;
                            } else {
                                console.error(response);
                            }
                        });
                    }
                });
            },

            async checkout() {
                if (!this.order_details.id) {
                    return;
                }

                if (isNaN(this.discount.amount)) {
                    this.discount.error = "The amount must be a number";
                    return;
                }


                if(this.discount.name != ''){
                    if(this.discount.amount === '' || this.discount.amount <= 0) {
                        this.discount.error = "The amount must be greater than 0.";
                        return;
                    }

                }

                this.is_processing = true;
                this.general_error = null;
                this.amount = this.order_details.total_amount;

                await axios.post(this.base_url + 'order/' + this.order_details.id + '/checkout').then(({data}) => {
                    this.openAskForPaymentMethods(data);
                });
            },

            searchProduct() {
                this.is_searching_product = true;
                this.product.is_show_popup = true;

                clearTimeout(this.order.timeout);

                this.order.timeout = setTimeout(() => {
                    if (this.order.keywords === '') {
                        this.order.search_results = [];
                    } else {
                        axios.post(this.base_url + 'product', {
                            keywords: this.order.keywords,
                        }).then(({data}) => {
                            this.is_searching_product = false;
                            this.order.search_results = data;
                            this.product.is_show_popup = true;
                        });
                    }
                }, 500);
            },

            async getOrder() {
                if (this.order_details.id === undefined) {
                    this.order_details = await axios.post(this.base_url + 'order').then(({data}) => data);
                }

                return this.order_details.id;
            },

            tryToAddCustomer() {
                this.is_show_search_customer = true;
            },

            searchCustomer() {
                this.customer.search_state = 'searching';
                clearTimeout(this.customer.timeout);

                this.customer.timeout = setTimeout(() => {
                    if (this.customer.keywords === '') {
                        this.customer.search_state = 'initial';
                        this.customer.search_results = [];
                    } else {
                        axios.post(this.base_url + 'customer', {
                            keywords: this.customer.keywords,
                        }).then(({data}) => {
                            this.customer.search_state = 'done';
                            this.customer.search_results = data;
                            this.customer.is_show_popup = true;
                        });
                    }
                }, 500);
            },

            async addCustomer(id) {
                let orderId = await this.getOrder();

                axios.post(this.base_url + 'order/' + orderId + '/customer', {
                    customer_id: id,
                }).then(({data}) => {
                    this.order_details = data;

                    this.customer.keywords = '';
                    this.customer.search_state = 'done';
                    this.customer.search_results = [];
                    this.customer.is_show_popup = false;
                })
            },

            searchDiscount() {
                this.discount.keywords = this.discount.name;
                this.discount.search_state = 'searching';
                clearTimeout(this.discount.timeout);

                this.discount.timeout = setTimeout(() => {
                    if (this.discount.name === '') {
                        this.discount.search_state = 'initial';
                        this.discount.search_results = [];
                    } else {
                        axios.post(this.base_url + 'discounts', {
                            keywords: this.discount.keywords,
                        }).then(({data}) => {
                            this.discount.is_show_popup = true;
                            this.discount.search_state = 'done';
                            this.discount.search_results = data;
                        });
                    }
                }, 500);
            },

            async addDiscount(id) {
                this.discount.is_show_popup = false;
                let discountItem = this.discount.search_results.find(x => x.id === id);
                if( discountItem.fixed_amount == 0) {
                    this.discount.amount = Number(discountItem.percentage * this.order_details.total_amount).toFixed(2);
                } else {
                    this.discount.amount = discountItem.fixed_amount
                }

                this.discount.name = discountItem.name;


                this.setDiscountAmount();
            },

            async removeDiscount() {
                this.discount.name = '';
                this.discount.amount = 0;
                this.is_show_discount = false;
                this.setDiscountAmount();
            },

            async removeCustomer(id) {
                let orderId = await this.getOrder();

                axios.delete(this.base_url + 'order/' + orderId + '/customer', {
                    customer_id: id,
                }).then(({data}) => {
                    this.order_details = data;
                    this.customer.modal.modal('hide');
                })
            },

            addDiscountModal() {
                // this.discount.name = this.order_details.discount_name ? this.order_details.discount_name : 'Discount';
                // this.discount.amount = this.order_details.discount_amount;
                // this.discount.modal.modal('show');
                this.is_show_discount = true;
            },

            async setDiscountAmount() {
                this.is_processing = true;
                this.discount.error = '';

                let discount = this.discount.amount;
                if(discount == '')
                    discount = 0;

                if(isNaN(discount)){
                    this.discount.error = "The amount must be a number";
                    this.is_processing = false;
                    return;
                }

                let orderId = await this.getOrder();

                axios.post(this.base_url + 'order/' + orderId + '/discount', {
                    name: this.discount.name,
                    amount: discount,
                }).then(({data}) => {
                    this.is_processing = false;
                    this.order_details = data;
                })
            },

            addTaxModal() {
                this.tax_setting.name = this.order_details.tax_setting_name ? this.order_details.tax_setting_name : 'Tax Setting';
                this.tax_setting.amount = this.order_details.tax_setting_amount;
                this.tax_setting.modal.modal('show');
            },
            async setTaxAmount(){
                let orderId = await this.getOrder();

                axios.post(this.base_url + 'order/' + orderId + '/tax-setting', {
                    tax_setting_id: this.tax_setting.id,
                }).then(({data}) => {
                    this.order_details = data;
                    this.tax_setting.modal.modal('hide');
                })
            },

            async addProduct(id, close = false) {
                let orderId = await this.getOrder();

                axios.post(this.base_url + 'order/' + orderId + '/product', {
                    id: id,
                    quantity: 1,
                }).then(async ({data}) => {
                    this.order_details = data;
                    this.product.is_show_popup = false;

                    if (close && this.order.modal) {
                        this.order.modal.modal('hide');
                    }

                    if (close && this.product.modal) {
                        this.product.modal.modal('hide');
                    }

                    await this.updateTerminalCart();
                }).catch(({response}) => {
                    if (response.status === 422) {
                        if (response.data.errors.quantity) {
                            alert(_.first(response.data.errors.quantity));
                        }
                    }
                });
            },

            async updateProduct(id) {
                this.updating_quantity = true;

                let orderId = await this.getOrder();

                axios.put(this.base_url + 'order/' + orderId + '/product/' + id, {
                    quantity: this.to_be_updated.quantity,
                    remark: this.to_be_updated.remark,
                }).then(async ({data}) => {
                    this.order_details = data;
                    this.updating_quantity = false;

                    await this.updateTerminalCart();
                });
            },

            async removeProduct(id) {
                let orderId = await this.getOrder();

                axios.delete(this.base_url + 'order/' + orderId + '/product/' + id).then(async ({data}) => {
                    this.order_details = data;

                    await this.updateTerminalCart();
                });
            },

            async updateTerminalCart() {
                if (this.terminals.connected) {
                    let products = [];

                    _.forEach(this.order_details.products, (product) => {
                        let name = product.name;

                        if (product.variation_value_1) {
                            name = name + ' ' + product.variation_value_1;
                        }

                        if (product.variation_value_2) {
                            name = name + ' ' + product.variation_value_2;
                        }

                        if (product.variation_value_3) {
                            name = name + ' ' + product.variation_value_3;
                        }

                        products.push({
                            description: name,
                            amount: product.actual_total_price,
                            quantity: product.quantity,
                        });
                    });

                    await this.terminals.stripe.setReaderDisplay({
                        type: 'cart',
                        cart: {
                            line_items: products,
                            tax: this.order_details.actual_tax_amount,
                            total: this.order_details.actual_total_amount,
                            currency: this.currency,
                        },
                    });
                }
            },

            clearTerminalCart() {
                if (this.terminals.connected) {
                    this.terminals.stripe.clearReaderDisplay();
                }
            },

            editQuantity(index) {
                if (this.order_details.products[index]) {
                    this.to_be_updated = this.order_details.products[index];
                }
            },

            async sendReceipt() {
                if (!this.recipient) {
                    return this.recipient_error = 'The recipient email can\'t be empty.'
                } else if (!this.validateEmail(this.recipient)) {
                    return this.recipient_error = 'The entered email is invalid.'
                }

                this.is_sending_receipt = true;
                this.recipient_error = null;

                await axios.post(this.getDomain('business/' + Business.id + '/charge/' + this.charges.charge_object.id + '/receipt', 'dashboard'), {
                    email: this.recipient,
                }).then(({data}) => {
                    this.is_sending_receipt = false;
                    this.is_receipt_sent = true;
                }).catch(({response}) => {
                    this.is_sending_receipt = false;
                    this.recipient_error = response.data.message;
                });
            },

            copy() {
                let link = document.querySelector('#payment_link')
                link.setAttribute('type', 'text')
                link.select()

                let $this = this;

                try {
                    let copied = document.execCommand('copy');
                    if (copied) {
                        $this.charges.is_payment_link_copied = true;
                    }
                } catch (err) {
                    alert('Oops, unable to copy');
                }

                /* unselect the range */
                link.setAttribute('type', 'hidden')
                window.getSelection().removeAllRanges()

                setTimeout(function () {
                    $this.charges.is_payment_link_copied = false;
                }, 3000)
            },

            customerLink() {
                return this.getDomain('business/' + Business.id + '/customer/create', 'dashboard');
            },

            productLink() {
                return this.getDomain('business/' + Business.id + '/product/create', 'dashboard');
            },

            discountLink() {
                return this.getDomain('business/' + Business.id + '/discount/create', 'dashboard');
            },

            updateFeaturedProducts(index) {
                this.activeCategory = index;
                let link = '';

                if(this.activeCategory === 'home')
                    link = this.base_url+ 'category/home';
                else
                    link = this.base_url+ 'category/' + this.categories[index].id;

                axios.post(link).then(({data}) => {
                    let featuredProducts = data.featured_products;
                    let featuredProductsWithAttrs = [];
                    let featuredProductsAttrs = data.featured_product_attrs;

                    let j = 0;
                    _.each(featuredProducts, (featuredProduct) => {
                        featuredProduct.imageSrc = featuredProductsAttrs['image'][j];
                        featuredProduct.showPrice = featuredProductsAttrs['price'][j];
                        featuredProduct.available = featuredProductsAttrs['available'][j];

                        featuredProductsWithAttrs.push(featuredProduct);
                        j++;
                    });
                    this.featured_products = featuredProductsWithAttrs;
                });
            },

            decreaseQuantity(index) {
                this.to_be_updated = this.order_details.products[index];

                if(this.to_be_updated.quantity <= 1)
                    return;

                this.to_be_updated.quantity--;

                this.updateProduct(this.to_be_updated.id);
            },

            increaseQuantity(index) {
                this.to_be_updated = this.order_details.products[index];

                this.to_be_updated.quantity++;

                this.updateProduct(this.to_be_updated.id);
            },

            enterQuantity(index) {
                this.to_be_updated = this.order_details.products[index];

                if( this.to_be_updated.quantity <= 1){
                    return;
                }

                this.to_be_updated.quantity;

                this.updateProduct(this.to_be_updated.id);
            },

            onChangePage(pageOfItems) {
                this.pageOfProducts = pageOfItems;
            },

            addFeaturedProduct(id) {
                let product = this.pageOfProducts.find(x => x.id === id);
                if(product.has_variations == false) {
                    this.addProduct(product.variations[0].id);
                } else{
                    this.product.selected_product = product;
                    if (product.is_manageable) {
                        _.each(product.variations, (variation) => {
                            if (!this.product.selected_variation_id && variation.quantity > 0) {
                                this.product.selected_variation_id = variation.id;
                            }
                        });
                    }
                    else{
                        this.product.selected_variation_id = product.variations[0].id;
                    }
                    this.product.modal.modal('show');
                }
            },

            addFeaturedProductVariation(){
                this.addProduct(this.product.selected_variation_id, true);
            },

            clickMain(e){
                if(e.target.id != "customer_search") {
                    if(e.target.closest('.customer-search-result') == null)
                        this.customer.is_show_popup = false;
                }

                if(e.target.id !="searchInput") {
                    if(e.target.closest('#search_product_popup') == null)
                        this.product.is_show_popup = false;
                }
            },
            resetOrder() {
                this.discount.name = '';
                this.discount.amount = 0;
                this.discount.keywords = ''
                this.is_show_discount = false;
                this.order_details = {
                    customer: null,
                    products: [],
                    tax_amount: 0,
                    discount_amount: 0,
                    total_amount: 0,
                    tax_setting: {}
                }
            }
        },
    }
</script>

<style lang="scss">
#paynow_online-qr-code img,
#grabpay-qr-code img,

.number-pad {
    border-color: #ced4da;
    color: #343a40;
}

.number-pad.top-left {
    border-top-left-radius: 3px;
}

.number-pad.top-right {
    border-top-right-radius: 3px;
}

.number-pad.bottom-left {
    border-bottom-right-radius: 3px;
}

.number-pad.bottom-right {
    border-bottom-left-radius: 3px;
}

.border-dashed {
    border-style: dashed !important;
}

#allow_repeated_payments {
    vertical-align: middle;
}

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
    margin: 0px 0px 25px;
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

.category__link {
   font-size: 1.05em;
   color: #929292;
   display: block;
   padding: 1em;
   cursor: pointer;
   -webkit-user-select: none;
   -moz-user-select: none;
   -ms-user-select: none;
   user-select: none;
   -webkit-touch-callout: none;
   -khtml-user-select: none;
   -webkit-tap-highlight-color: rgba(0, 0, 0, 0);
}

.category__link:hover,
.category__link:focus {
   outline: none;
}

.category__item--current .category__link {
    color: #FFF;
    font-weight: 500;
    background-color: #000036;
}

.poin-of-sale{
    .title-product{
        font-weight: 500;
    }
    .btn-tab-group{
        .btn{
            padding: 6px 20px 6px 20px;
            background: #FFF;
            border: none;
            &:hover{
                border: none;
            }
            &.active{
                background: #011B5F
            }
        }
    }
    .btn{
        &.btn-sm{
            height: 26px;
            padding: 0px 8px;
            min-width: 68px;
            .icon-plus{
                width: 7px;
                margin: 0px 3px 0px 0px;
            }
            @media screen and (min-width: 1200px) {
                padding: 0px 20px;
                .icon-plus{
                    margin: 0px 6px 0px 0px;
                }
            }
        }
    }
    .title{
        font-size: 13px;
    }
    .icon-plus{
        width: 11px;
        height: auto;
        position: relative;
        top: -1px;
        margin: 0px 12px 0px 0px;
    }
    .form-control{
        height: 40px;
        &:focus{
            box-shadow: none;
            border: 1px solid #ced4da;
        }
    }
    .input-group-text{
        height: 40px;
        padding: 0px 20px;
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

    .radio-toolbar input[type="radio"]:checked + label {
        background-color: #011B5F;
        color: #fff;
    }

    .valid-error {
        display: block;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 80%;
        color: #dc3545;
    }
    .main-content{
        input{
            &#amount{
                color: transparent;
                text-shadow: 0 0 0 #2196f3;
            }
        }
        .product-search{
            position: relative;
            .form-control{
                &:focus{
                    box-shadow: none;
                }
            }
            .search-result{
                width: 100%;
                position: absolute;
                background: #FFF;
                z-index: 99;
                .item{
                    padding: 18px 15px 18px 15px;
                    .title-product{
                        &.hl{
                            margin: 0px 0px 15px;
                        }
                    }
                    .variation{
                        display: none;
                        &:last-child{
                            padding-bottom: 0px !important;
                        }
                    }
                }
            }
        }
        .featured-product{
            margin-bottom: 40px;
            .mb-product{
                margin: 0 0 24px;
                .item{
                    background: #F8F9FA;
                    padding: 15px 15px 15px 15px;
                    .thumbnail{
                        position: relative;
                        background: #FFF;
                        background-size: contain;
                        background-position: center center;
                        background-repeat: no-repeat;
                        margin: 0px 0px 16px;
                        span{
                            padding: 0 0 100%;
                            display: block;
                            overflow: hidden;
                        }
                        img{
                            position: absolute;
                            width: 100%;
                            height: 100%;
                            z-index: -10;
                        }
                    }
                    .title-product{
                        font-size: 15px;
                    }
                    .price{
                        margin: 0px 0px 12px;
                        color: #545454;
                    }
                }
            }
            .empty-product{
                max-width: 300px;
                margin: 0 auto;
                padding: 16px 0px 0px;
                .icon{
                    margin: 0px 0px 26px;
                    img{
                        width: 93px;
                        height: auto;
                    }
                }
                .excerpt{
                    color: #545454;
                    margin: 0px 0px 29px;
                }
                .btn{
                    min-width: 211px;
                }
            }
        }
        .category{
            .category__list {
                margin: 0px 0px 25px;
                .category__item {
                    color: #1E1E1F;
                    align-self: flex-end;
                }
                .category__link {
                    padding: 10px 24px 10px;
                    color: #1E1E1F;
                }
                .category__item--current{
                    .category__link{
                        color: #FFF;
                        background-color: #1660EB;
                        border-radius: .25rem .25rem 0px 0px;
                        font-size: 15px;
                    }
                }
            }
        }
        .pagination-shop{
            width: 100%;
            padding: 20px 15px 0px;
            .pagination{
                .first{
                    a{
                        height: 40px;
                        padding-top: 10px !important;
                        border-left: 2px solid #1660EB !important;
                    }
                }
                .page-item{
                    &.active{
                        a{
                            background-color: #1660EB;
                        }
                    }
                }
                .page-number{
                    .page-link{
                        width: 34px;
                        height: 34px;
                        position: relative;
                        top: -2px;
                        padding: 7px 0px 0px 0px !important;
                    }
                }
                .last{
                    float: right;
                    a{
                        height: 40px;
                        padding-top: 10px !important;
                        border-right: 2px solid #1660EB !important;
                    }
                }
                @media (min-width: 768px) {
                    .last, .first{
                        a{
                            width: 108px;
                        }
                    }
                }
            }
        }
    }
    .sidebar{
        .btn-secondary{
            height: 40px;
        }
        .customer-information{
            .inner{
                padding: 15px 0px 8px;
            }
            .name{
                font-weight: 500;
                margin: 0px 0px 8px;
            }
        }
        .customer-search{
            position: relative;
            .search-result{
                width: 100%;
                position: absolute;
                background: #FFF;
                z-index: 9999;
                .item{
                    padding: 12px 15px 15px;
                    border-bottom: 1px solid #D4D6DD;
                    &:last-child{
                        border-bottom: none;
                    }
                    .information{
                        margin: 0px 0px 10px;
                        .name{
                            margin: 0px 0px 5px;
                            color: #03102F;
                        }
                        p{
                            font-size: 15px;
                            color: #545454;
                        }
                    }
                }
            }
            .empty-result{
                padding: 5px 0px 7px;
                .text{
                    color: #545454;
                }
                .btn{
                    width: 145px;
                    padding: 1px 10px 0px;
                }
            }
        }
        .order-products{
            .item{
                position: relative;
                padding: 30px 20px 20px 20px;
                border-bottom: 1px solid #D4D6DD;
                .thumbnail{
                    width: 82px;
                    float: left;
                    margin: 0px 15px 0px 0px;
                }
                .information{
                    width: calc(100% - 97px);
                    float: left;
                    .cart-number{
                        span{
                            width: 25px;
                            height: 25px;
                            border:  1px solid #D4D6DD;
                            text-align: center;
                            display: block;
                            float: left;
                            cursor: pointer;
                            padding: 0px 0px 0px;
                        }
                        input{
                            width: 25px;
                            height: 25px;
                            display: block;
                            float: left;
                            text-align: center;
                            border:  1px solid #D4D6DD;
                            border-left: none;
                            border-right: none;
                            background: #FFF;
                            border-radius: 0;
                            font-size: 13px;
                            -webkit-appearance: none;
                            -moz-appearance: none;
                            appearance: none;
                            &:focus{
                                outline: none;
                            }
                        }
                    }
                }
                .btn-close{
                    position: absolute;
                    right: 20px;
                    top: 5px;
                    img{
                        width: 10px;
                        height: auto;
                    }
                }
            }
        }
        .frm-discount{
            .ctn-inner{
                position: relative;
            }
            .btn-close{
                position: absolute;
                right: 2px;
                top: -5px;
                img{
                    width: 15px;
                    height: auto;
                }
            }
            .discount{
                .name{
                    margin: 0px 0px 8px;
                }
                .search-result{
                    width: 100%;
                    position: absolute;
                    background: #FFF;
                    z-index: 99;
                    top: 40px;
                    .item{
                        padding: 18px 15px 18px 15px;
                        border-bottom: 1px solid #D4D6DD;
                        &:last-child{
                            border-bottom: none;
                        }
                        .amount{
                            margin: 0px 0px 15px;
                        }
                    }
                }
            }
        }
    }
    .modal-featured-product{
        .close{
            position: absolute;
            top: 5px;
            img{
                width: 13px;
                height: auto;
            }
        }
        .modal-body{
            padding: 15px 15px 25px 15px;
            .thumbnail{
                margin: 0px 0px 20px 0px;
                img{
                    width: 100%;
                    height: auto;
                }
            }
            .radio-toolbar{
                input[type="radio"]:checked + label{
                    border: 1px solid #011B5F;
                }
                label{
                    border-radius: 4px;
                }
            }
            .modal-product-title{
                font-size: 18px;
                font-weight: 500;
                margin: 0px 0px 20px;
            }
            .price{
                font-size: 18px;
                font-weight: 500;
                color: #03102F;
                padding: 10px 0px 5px;
            }
            .btn{
                width: 144px;
                height: 40px;
            }
        }
        @media (min-width: 768px) {
            .modal-body{
                padding: 30px 30px 30px 30px;
                .thumbnail{
                    margin: 0px;
                    img{
                        width: 134px;
                        height: auto;
                    }
                }
            }
        }
        @media screen and (max-width: 575px) {
            .modal-body{
                .btn{
                    width: 100%;
                }
            }
        }
    }
}
</style>
