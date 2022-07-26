<template>
    <div>
        <div v-if="!is_hide_get_started" class="online-shop-onboarding shadow-sm">
            <div class="top-title d-flex justify-content-between align-items-center">
                <h4>Get Started With Your Online Shop - It's Free</h4>
                <span @click="clickDismiss()">Dismiss</span>
            </div>
            <template>
                <div class="online-store-steps clearfix">
                    <ul class="clearfix">
                        <li :class="[(is_store_url && step != 0 ? 'active' : ''), ((step == 0)? 'current': '')]" @click="forceStep(0)">
                            <a href="#">Store URL</a>
                            <span></span>
                        </li>
                        <li :class="[(step > 1) || (is_theme && step != 1) ? 'active' : '', ((step == 1)? 'current': '')]" @click="(step > 1 || is_store_url) ? forceStep(1) : $event.preventDefault()">
                            <a href="#" >Theme</a>
                            <span></span>
                        </li>
                        <li :class="[(step > 2) || (is_products && step != 2) ? 'active' : '', ((step == 2)? 'current': '') ]" @click="(step > 2 || is_theme) ? forceStep(2) : $event.preventDefault()">
                            <a href="#">Add Products</a>
                            <span></span>
                        </li>
                        <li :class="[(step > 3) || (is_shipping_and_pickup && step != 3) ? 'active' : '', ((step == 3)? 'current': '')]" @click="(step > 3 || is_products) ? forceStep(3) : $event.preventDefault()">
                            <a href="#">Shipping & Pickup</a>
                            <span></span>
                        </li>
                        <li :class="(step === 4) || is_ready ? 'active current' : ''" @click="(step > 4 || is_shipping_and_pickup) ? forceStep(4) : $event.preventDefault()">
                            <a href="#">Online Shop is Ready!</a>
                            <span></span>
                        </li>
                    </ul>
                </div>
            </template>
            <template v-if="step==0">
                <div class="store-url">
                    <h5>Store URL</h5>
                    <div class="excerpt">
                        <p>Setup a homepage URL so that the customers can reach the business only</p>
                    </div>
                    <div class="form-url">
                        <div class="input-group">
                            <div class="input-group-append">
                                <span class="input-group-text" id="basic-addon2">{{ shop_url }}</span>
                            </div>
                            <input v-model="store_url" id="store_url" class="form-control" aria-label="store url" aria-describedby="basic-addon2" @input="changeUrl($event.target.value)" :class="{
                            'is-invalid': errors.store_url,
                            'bg-white': !(is_processing || is_succeeded),
                        }" :disabled="is_processing || is_succeeded">
                        </div>
                        <span v-if="errors.store_url" class="d-block small text-danger w-100 mt-1" role="alert">
                            {{ errors.store_url }}
                        </span>
                    </div>
                    <div class="is-btn-step">
                        <button class="btn btn-primary d-block" :disabled="is_processing" @click="saveStoreUrl()">Next step</button>
                    </div>
                </div>
            </template>
            <template v-if="step==1">
                <h5>Theme</h5>
                <div class="excerpt">
                    <p>Select the color theme for your storefront's appearance</p>
                </div>
                <div class="all-theme-options">
                    <div class="row row-container">
                        <ShopTheme
                            v-for="(t, index) in themes"
                            :key="index"
                            :theme="t"
                            :current="theme"
                            :custom="t.custom"
                            @theme="theme = $event"
                            @color="onChangeCustomColor"
                            />
                    </div>
                </div>
                <div class="is-btn-step">
                    <button class="btn btn-secondary" :disabled="is_processing" @click="forceStep(0)">Previous step</button>
                    <button class="btn btn-primary ml-3" :disabled="is_processing" @click="saveTheme()">Next step</button>
                </div>
            </template>
            <!-- Add product -->
            <template v-if="step==2">
                <h5>Add Products</h5>
                <div class="excerpt">
                    <p>Add a new product to your store</p>
                </div>
                <div class="add-product">
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow-sm mb-3">
                                <div class="card-body p-4">
                                    <div class="row">
                                        <div class="col-lg-6 col-sm-12">
                                            <div class="form-group">
                                                <label for="product_name">Product name</label>
                                                <input id="product_name" type="text" v-model="product.name" :class="{
                                                    'is-invalid' : errors.name,
                                                }" class="form-control" title="Product Name" placeholder="T-Shirt">
                                                <span v-if="errors.name" class="invalid-feedback" role="alert">{{ errors.name }}</span>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-sm-12">
                                            <div class="form-group">
                                                <label for="product_price">Price</label>
                                                <div class="input-group">
                                                    <input id="product_price" type="text" v-model="product.price" :class="{
                                                        'is-invalid' : errors.product_price
                                                    }" class="form-control" placeholder="10.00" step="0.01" title="Selling Price" aria-describedby="basic-addon3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text bg-light-gray" id="basic-addon3">{{ product_setting.currency }}</span>
                                                    </div>
                                                </div>
                                                <span v-if="errors.product_price" class="d-block small text-danger w-100 mt-1" role="alert">
                                                    {{ errors.product_price }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-12 col-sm-12">
                                            <label for="">Images</label><br/>
                                            <div v-if="(product.images).length < product.imageLimit">
                                                <div class="d-lg-flex mb-3 align-items-center">
                                                    <label class="btn-upload d-inline-flex" for="productImage">
                                                        <input type="file" id="productImage" class="custom-file-input d-none" accept="image/*"
                                                            @change="addImage($event)" multiple="multiple">
                                                        <span id="uploadBtn" class="btn btn-primary">
                                                            Choose image
                                                        </span>
                                                    </label>
                                                    <template v-for="(name,key) in product.images_name">
                                                        <div class="file-name" :key="key">
                                                            <span>{{ name }}</span> <br/>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                            <p>The optimal product image size is 600*600, 800*800 and 1000*1000.<br>
                                            You can upload up to 6 images</p>

                                            <template v-if="(product.images).length > 0">
                                            <div class="row mt-4">
                                                <template v-for="(image,key) in product.images">
                                                    <div class="col-md-4 mb-4" :key="key">
                                                        <div class="mb-4">
                                                            <a class="text-danger" href="#" @click="removeImage($event, key)">Remove</a>
                                                        </div>
                                                        <img :src="image" class="img-fluid rounded" style="max-width: 200px">
                                                    </div>
                                                </template>
                                            </div>
                                            </template>
                                        </div>
                                    </div>
                                    <p class="view-option" v-if="!is_add_option" @click="addOptions()">More options</p>
                                    <template v-if="is_add_option">
                                        <div class="add-option">
                                            <div class="item">
                                                <a class="label toggle-label" data-toggle="collapse" href="#option_description"  aria-expanded="false" aria-controls="option_description">Description</a>
                                                <div class="collapse multi-collapse mt3" id="option_description">
                                                    <textarea id="description" v-model="product_option.description" :class="{ 'is-invalid' : errors.description }"
                                                          class="form-control bg-light" rows="4" title="Description"
                                                          placeholder="A T-shirt is a style of unisex fabric shirt named after the T shape of its body and sleeves. Traditionally it has short sleeves and a round neckline, known as a crew neck, which lacks a collar…"></textarea>
                                                    <span v-if="errors.description" class="invalid-feedback" role="alert">{{
                                                        errors.description
                                                    }}</span>
                                                </div>
                                            </div>
                                            <div class="item">
                                                <a class="label toggle-label" data-toggle="collapse" href="#inventory"  aria-expanded="false" aria-controls="inventory">Manage inventory</a>
                                                <div class="collapse multi-collapse mt3" id="inventory">
                                                    <div class="row">
                                                        <div class="col-lg-6 col-sm-12">
                                                            <div class="form-group">
                                                                <label for="product_available_quantity">Available Quantity</label>
                                                                <input id="product_available_quantity" type="text" v-model="product_option.quantity"  :class="{'is-invalid' : errors.available_quantity}"
                                                                class="form-control" title="Available Quantity" placeholder="0" @keydown="disallowDecimal($event)">
                                                                <span v-if="errors.available_quantity" class="invalid-feedback" role="alert">{{ errors.available_quantity }}</span>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-6 col-sm-12">
                                                            <div class="form-group">
                                                                <label for="product_quantity_alert">Low Quantity Alert</label>
                                                                <input id="product_quantity_alert" type="text" v-model="product_option.quantity_alert_level"  :class="{'is-invalid' : errors.quantity_alert}"
                                                                class="form-control" title="Low Quantity Alert" placeholder="0" @keydown="disallowDecimal($event)">
                                                                <span v-if="errors.quantity_alert" class="invalid-feedback" role="alert">{{ errors.quantity_alert }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="item">
                                                <a class="label toggle-label" data-toggle="collapse" href="#featured"  aria-expanded="false" aria-controls="featured">Featured products</a>
                                                <div class="collapse multi-collapse mt3" id="featured">
                                                    <div class="custom-control custom-switch is-custom-switch">
                                                        <input id="switch-store" v-model="product_option.featured" type="checkbox"
                                                            class="custom-control-input"
                                                            :disabled="is_processing">
                                                        <label v-if="product_option.featured" for="switch-store" class="custom-control-label">Enable featured products</label>
                                                        <label v-if="!product_option.featured" for="switch-store" class="custom-control-label">disable featured products</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="item">
                                                <a class="label toggle-label" data-toggle="collapse" href="#variants"  aria-expanded="false" aria-controls="variants">Variants</a>
                                                <div class="collapse multi-collapse mt-3" id="variants">
                                                    <p>
                                                        <span class="mt-2 mb-3">Add variants such as size and color</span>
                                                    </p>
                                                    <p>
                                                        <a :class="{ 'text-danger' : product_option.has_variation }"
                                                        @click="triggerVariation($event, !product_option.has_variation)"
                                                        href="#" class="a-link">
                                                            {{ product_option.has_variation ? 'Disable variants' : 'Enable variants' }}
                                                        </a>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="item">
                                              <a class="label toggle-label" data-toggle="collapse" href="#option_categories"  aria-expanded="false" aria-controls="option_categories">Product Categories</a>
                                              <div class="collapse multi-collapse mt3" id="option_categories">
                                                <multiselect v-model="product_option.categories" :options="allCategories" :multiple="true" :close-on-select="true"
                                                             :clear-on-select="false" placeholder="Choose categories" label="name"
                                                             track-by="name" :max="5"></multiselect>
                                                <span v-if="errors.categories" class="invalid-feedback" role="alert">{{
                                                    errors.categories
                                                  }}</span>
                                              </div>
                                            </div>
                                            <div v-for="variation in variations">
                                                <div class="form-group row mb-0">
                                                    <label class="col-12 col-form-label">
                                                        <a class="small text-danger float-right" href="#"
                                                        @click="removeVariant($event, variation.key)">Remove</a>
                                                        <span class="font-weight-bold">{{ usable_data.variation_list[variation.key] }}</span>
                                                        <small>* Press enter after entering each variant name</small>
                                                    </label>
                                                    <div class="col-12">
                                                        <div class="fake-form-control bg-light rounded">
                                                            <ul v-if="variations" class="list-inline d-inline mb-0">
                                                                <li v-for="(value, index) in variation.value" class="list-inline-item mr-1">
                                                                    <span class="badge badge-secondary font-weight-normal badge-lg mb-1">
                                                                        {{ value }}
                                                                        <a href="#" class="text-white-50"
                                                                        @click="removeVariantChild($event, index, variation.value)">
                                                                            <i class="far fa-times-circle"></i>
                                                                        </a>
                                                                    </span>
                                                                </li>
                                                            </ul>
                                                            <input @blur="appendListWhenBlur($event, variation)"
                                                                @keydown="appendList($event, variation)"
                                                                class="inline-input form-control border-0 w-100 bg-light p-0" title="value">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div v-if="product_option.has_variation" :class="{ 'mt-3' : variations.length < 3 }">
                                                <a v-for="(item, index) in usable_data.variation_list" v-if="!variations.find(x => x.key === index)"
                                                @click="addVariation($event, index)" class="btn btn-outline-secondary btn-sm mr-1" href="#">
                                                    + {{ item }}
                                                </a>
                                            </div>
                                            <div v-if="product_option.variations.length > 0" class="custom-control custom-checkbox mt-3">
                                                <input type="checkbox" class="custom-control-input" id="isVariationManageable"
                                                    v-model="product_option.is_variation_manageable">
                                                <label class="custom-control-label" for="isVariationManageable">Manage Variants Inventory</label>
                                            </div>
                                        </div>
                                        <table v-if="product_option.has_variation && product_option.variations.length > 0"
                                           class="table table-hover border-top mb-0">
                                        <tr class="bg-light">
                                            <th scope="col">Variants</th>
                                            <th scope="col">Selling Price</th>
                                            <th scope="col" v-if="product_option.is_variation_manageable">Quantity</th>
                                            <th scope="col" v-if="product_option.is_variation_manageable">Low Quantity Alert</th>
                                        </tr>
                                        <template v-for="(item, key) in product_option.variations">
                                            <tr :class="{
                                                'alert-danger': product_option.variation_errors[item.id]
                                            }">
                                                <td>
                                                    <div class="col-form-label">
                                                        <ul class="special list-inline d-inline mb-0">
                                                            <li v-for="v in item.values" class="list-inline-item">{{ v.value }}</li>
                                                        </ul>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">{{ product_setting.currency }}</span>
                                                        </div>
                                                        <input v-model="item.price" title="Selling Price"
                                                               class="form-control" :placeholder="product.price" step="0.01">
                                                    </div>
                                                </td>
                                                <td v-if="product_option.is_variation_manageable">
                                                    <input v-model="item.quantity" title="Quantity" class="form-control"
                                                           @keydown="disallowDecimal($event)">
                                                </td>
                                                <td v-if="product_option.is_variation_manageable">
                                                    <input v-model="item.quantity_alert_level" title="Quantity" class="form-control"
                                                           :class="{'bg-light': item.quantity !== null && item.quantity !== ''}"
                                                           :disabled="item.quantity === null || item.quantity === ''"
                                                           @keydown="disallowDecimal($event)">
                                                </td>
                                            </tr>
                                            <tr v-if="product_option.variation_errors[item.id]">
                                                <td :colspan="product_option.is_variation_manageable ? 5 : 3"
                                                    class="alert-danger border-left-0 border-right-0 border-bottom-0 border-alert-danger rounded-0">
                                                    <ul class="list-unstyled small mb-0">
                                                        <li v-for="error in product_option.variation_errors[item.id]">
                                                            <i class="fas fa-caret-right fa-fw"></i> {{ error }}
                                                        </li>
                                                    </ul>
                                                </td>
                                            </tr>
                                        </template>
                                    </table>
                                    </template>
                                  <p v-if="product_success_message" class="text-success font-weight-bold mb-3 mt-3">
                                    Added successfully!</p>
                                    <div class="btn-bottom">
                                        <button id="publish_product" class="btn btn-primary btn-block" :disabled="is_processing" @click="publishProduct(true)">Publish product</button>
                                        <button id="draft_product" class="btn btn-secondary" :disabled="is_processing" @click="publishProduct(false)">Save as draft</button>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <a class="view-product" href="#" @click="redirecProductList()">View products</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="is-btn-step">
                    <button class="btn btn-secondary" :disabled="is_processing" @click="forceStep(1)">Previous step</button>
                    <button id="save_product" class="btn btn-primary ml-3" :disabled="is_processing" @click="saveProduct()">Next step</button>
                </div>
            </template>
            <!-- Shipping & Pick up -->
            <template v-if="step==3">
                <h5>Shipping & Pick up</h5>
                <div class="excerpt">
                    <p>Select how customers receive their products</p>
                </div>
                <div class="shipping-pickup">
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow-sm mb-3">
                                <div class="card-body p-4">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="item">
                                                <div class="custom-control custom-switch is-custom-switch">
                                                    <input id="switch-shipping" v-model="shipping_type.shipping" type="checkbox"
                                                        class="custom-control-input">
                                                  <label v-if="shipping_type.shipping" for="switch-shipping" class="custom-control-label">Shipping is
                                                    enabled</label>
                                                  <label v-if="!shipping_type.shipping" for="switch-shipping" class="custom-control-label">Shipping is
                                                    disabled</label>                                                  </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Shipping block -->
                                    <template v-if="shipping_type.shipping && business.shippings_count > 1">
                                      <a :href="this.getDomain(`business/${business_id}/setting/shipping`, 'dashboard')" class="a-link">View shipping methods</a>
                                    </template>
                                    <template v-else-if="shipping_type.shipping">
                                        <div class="shiping">
                                            <div class="row">
                                                <div class="col-12 col-sm-6 col-lg-6">
                                                    <div class="form-group">
                                                        <label for="country">Country</label>
                                                        <select id="country" class="custom-select is-dropdown" v-model="shipping.country" :class="{
                                                            'is-invalid' : errors.country
                                                        }" :disabled="is_processing">
                                                            <option v-for="country in countries" :value="country.code">
                                                                {{ country.name }}
                                                            </option>
                                                        </select> <span class="invalid-feedback" role="alert">{{ errors.country }}</span>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-sm-6 col-lg-6">
                                                    <div class="form-group">
                                                        <label for="calculation">Calculation</label>
                                                        <select id="calculation" class="custom-select is-dropdown" v-model="shipping.calculation" :class="{
                                                            'is-invalid' : errors.calculation
                                                        }" :disabled="is_processing">
                                                            <option v-for="calculation in calculations" :value="calculation.code">
                                                                {{ calculation.name }}
                                                            </option>
                                                        </select> <span class="invalid-feedback" role="alert">{{ errors.calculation }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-12 col-sm-6 col-lg-6">
                                                    <div class="form-group">
                                                        <label for="method_name">Shipping method name</label>
                                                        <input id="method_name" type="text" v-model="shipping.method_name" :class="{
                                                            'is-invalid' : errors.method_name,
                                                        }" class="form-control" title="Shipping method name" placeholder="Free">
                                                        <span v-if="errors.method_name" class="invalid-feedback" role="alert">{{ errors.method_name }}</span>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-sm-6 col-lg-6">
                                                    <div class="form-group">
                                                        <label for="rate">Rate</label>
                                                        <div class="input-group">
                                                            <input id="rate" type="number" class="form-control is-form-arrow" step="0.01" v-model="shipping.rate"
                                                                :class="{
                                                                'is-invalid' : errors.rate
                                                            }" placeholder="9.99" :disabled="is_processing">
                                                            <div class="input-group-prepend">
                                                            <span class="input-group-text" :class="{
                                                                'border-danger bg-danger text-white' : errors.rate
                                                            }">{{ shipping.currency }}</span>
                                                            </div>
                                                        </div>
                                                        <span class="small text-danger">{{ errors.rate }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-12 col-sm-12">
                                                    <div class="form-group">
                                                        <label for="description">Description</label>
                                                        <textarea  id="description" class="form-control" v-model="shipping.description" :class="{
                                                            'is-invalid' : errors.shipping_description}" title="Description" placeholder=""></textarea>
                                                        <span v-if="errors.shipping_description" class="invalid-feedback" role="alert">{{ errors.shipping_description }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-12 col-sm-12">
                                                    <label>Delivery date and time slots</label>
                                                    <p>Add date and time slots for your delivery</p>
                                                    <p>
                                                        <a href="#" class="a-link" @click="triggerSlots($event, !shipping.has_slots)">{{ shipping.has_slots ? 'Disable slots' : 'Enable slots' }}</a>
                                                    </p>
                                                </div>
                                            </div>
                                            <div v-if="shipping.has_slots" :class="{ 'mt-2' : shipping.slots.length < 3 }">
                                                <a v-for="(item, index) in usable_data.week_days_list"
                                                v-if="!shipping.slots.find(x => x.day === index)"
                                                @click="addSlot($event, index)" class="btn btn-outline-secondary btn-sm mr-1 mb-1" href="#">
                                                    + {{ item }}
                                                </a>
                                            </div>
                                            <template v-if="shipping.has_slots">
                                                <div v-for="slot in shipping.slots" class="mt-2">
                                                    <div class="form-group row mb-0">
                                                        <label class="col-12 col-form-label">
                                                            <a class="small text-danger float-right" href="#"
                                                            @click="removeSlot($event, slot.day)">Remove</a>
                                                            <span class="font-weight-bold">{{ usable_data.week_days_list[slot.day] }}</span>
                                                        </label>
                                                        <div class="col-12">
                                                            <span class="mr-2">From: </span>
                                                            <vue-timepicker format="hh:mm A" v-model="slot.times.from"
                                                                            close-on-complete></vue-timepicker>
                                                            <span class="mr-2">To: </span>
                                                            <vue-timepicker format="hh:mm A" v-model="slot.times.to"
                                                                            close-on-complete></vue-timepicker>
                                                            <span v-if="slot.error != ''" class="invalid-feedback d-block"
                                                                role="alert">{{ slot.error }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="item">
                                                <div class="custom-control custom-switch is-custom-switch">
                                                    <input id="switch-pickup" v-model="shipping_type.pickup" type="checkbox"
                                                        class="custom-control-input">
                                                  <label v-if="shipping_type.pickup" for="switch-pickup" class="custom-control-label">Pickup is
                                                    enabled</label>
                                                  <label v-if="!shipping_type.pickup" for="switch-pickup" class="custom-control-label">Pickup is
                                                    disabled</label>                                                 </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Shipping block -->
                                    <template v-if="shipping_type.pickup">
                                        <div class="pickup">
                                            <div class="row">
                                                <div class="col-12 col-sm-12">
                                                    <div class="form-group">
                                                        <label for="street">Street</label>
                                                        <input id="street" type="text" v-model="pickup.street" :class="{
                                                            'is-invalid' : errors.pickup_street
                                                        }" class="form-control" title="Street" placeholder="">
                                                        <span v-if="errors.pickup_street" class="invalid-feedback" role="alert">{{ errors.pickup_street }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-12 col-sm-6 col-lg-6">
                                                    <div class="form-group">
                                                        <label for="city">City</label>
                                                        <input id="city" type="text" v-model="pickup.city" :class="{
                                                            'is-invalid' : errors.city
                                                        }" class="form-control" title="Pickup city" placeholder="">
                                                        <span v-if="errors.city" class="invalid-feedback" role="alert">{{ errors.city }}</span>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-sm-6 col-lg-6">
                                                    <div class="form-group">
                                                        <label for="state">State</label>
                                                        <input id="state" type="text" v-model="pickup.state" :class="{
                                                            'is-invalid' : errors.state
                                                        }" class="form-control" title="Pickup state" placeholder="">
                                                        <span v-if="errors.state" class="invalid-feedback" role="alert">{{ errors.state }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-12 col-sm-6 col-lg-6">
                                                    <div class="form-group">
                                                        <label for="postal-code">Postal code</label>
                                                        <input id="postal-code" type="text" v-model="pickup.postal_code" :class="{'is-invalid' : errors.postal_code}" class="form-control" title="Postal code" placeholder="">
                                                        <span v-if="errors.postal_code" class="invalid-feedback" role="alert">{{ errors.postal_code }}</span>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-sm-6 col-lg-6">
                                                    <div class="form-group">
                                                        <label for="pickup-country">Country</label>
                                                        <input id="pickup-country" type="text" v-model="pickup.country" :class="{ 'is-invalid' : errors.pickup_country }" class="form-control" title="Country" placeholder="">
                                                        <span v-if="errors.pickup_country" class="invalid-feedback" role="alert">{{ errors.pickup_country }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-12 col-sm-12">
                                                    <label>Delivery date and time slots</label>
                                                    <p>Add date and time slots for your delivery</p>
                                                    <p>
                                                        <a href="#" class="a-link fw-500" @click="triggerPickupSlots($event, !pickup.has_slots)">{{ pickup.has_slots ? 'Disable slots' : 'Enable slots' }}</a>
                                                    </p>
                                                </div>
                                            </div>
                                            <div v-if="pickup.has_slots" :class="{ 'mt-3' : pickup.slots.length < 3 }">
                                                <a v-for="(item, index) in usable_data.week_days_list"
                                                v-if="!pickup.slots.find(x => x.day === index)"
                                                @click="addPickupSlot($event, index)" class="btn btn-outline-secondary btn-sm mr-1 mb-1" href="#">
                                                    + {{ item }}
                                                </a>
                                            </div>
                                            <template v-if="pickup.has_slots">
                                                <div v-for="slot in pickup.slots">
                                                    <div class="form-group row mb-0">
                                                        <label class="col-12 col-form-label">
                                                            <a class="small text-danger float-right" href="#"
                                                            @click="removePickupSlot($event, slot.day)">Remove</a>
                                                            <span class="font-weight-bold">{{ usable_data.week_days_list[slot.day] }}</span>
                                                        </label>
                                                        <div class="col-12">
                                                            <span class="mr-2">From: </span>
                                                            <vue-timepicker format="hh:mm A" v-model="slot.times.from"
                                                                            close-on-complete></vue-timepicker>
                                                            <span class="mr-2">To: </span>
                                                            <vue-timepicker format="hh:mm A" v-model="slot.times.to"
                                                                            close-on-complete></vue-timepicker>
                                                            <span v-if="slot.error != ''" class="invalid-feedback d-block"
                                                                role="alert">{{ slot.error }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="is-btn-step">
                    <button class="btn btn-secondary" :disabled="is_processing" @click="forceStep(2)">Previous step</button>
                    <button class="btn btn-primary ml-3" :disabled="is_processing" @click="saveShipping()">Next step</button>
                </div>
            </template>
            <template v-if="step==4">
              {{triggerSurvey()}}
                <div class="smd-next-steps">
                  <template v-if="!is_hide_get_started">
                      <h5>Your online Shop is Ready!</h5>
                      <div class="excerpt">
                          <p>Customers can reach the home page via following URL:</p>
                      </div>
                      <div class="row">
                          <div class="col-12">
                              <div class="form-control share-link">
                                  {{ shop_url }}{{ business.identifier }}
                                  <span class="share">
                                      <img src="/images/ico-share.svg" @click="redirectStore()" />
                                  </span>
                                  <span class="copy">
                                      <img src="/images/ico-copy.svg" @click="copyStoreUrl()"/>
                                  </span>
                              </div>
                          </div>
                      </div>
                  </template>
                </div>
            </template>
        </div>
        <div v-if="is_hide_get_started || step==4" class="online-shop-onboarding shadow-sm">
            <div class="smd-next-steps">
                <div class="steps">
                     <div class="top-title d-flex justify-content-between align-items-center">
                         <h5>Next steps</h5>
                        <span v-if="!has_order && is_hide_get_started" @click="clickShowGetStarted()">Show Get Started</span>
                     </div>
                    <div class="row">
                        <div class="col-12 col-sm-6 col-lg-6">
                            <div class="item product-categories">
                                <div class="card shadow-sm mb-4">
                                    <div class="thumbnail">
                                        <img src="/images/ico-next-step-01.png"/>
                                    </div>
                                    <div class="information">
                                        <h6>Product categories</h6>
                                        <div class="text">
                                            <p>Organise your products and help your customers find what they're looking for.</p>
                                        </div>
                                        <button class="btn btn-primary d-block pt-1 pb-1 mt-3" :disabled="is_processing" @click="redirectCategory()">Add product categories</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-6">
                            <div class="item store-settings">
                                <div class="card shadow-sm mb-4">
                                    <div class="thumbnail">
                                        <img src="/images/ico-next-step-03.png"/>
                                    </div>
                                    <div class="information">
                                        <h6>Store settings</h6>
                                        <div class="text">
                                            <p>Upload a cover image, add about us section and custom thank you message.</p>
                                        </div>
                                        <button class="btn btn-primary d-block pt-1 pb-1 mt-3" :disabled="is_processing" @click="redirectStoreSetting()">Add settings</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-6">
                            <div class="item coupons">
                                <div class="card shadow-sm mb-4">
                                    <div class="thumbnail">
                                        <img src="/images/ico-next-step-02.png"/>
                                    </div>
                                    <div class="information">
                                        <h6>Coupons</h6>
                                        <div class="text">
                                            <p>Customers will get a discount if they apply coupon on checkout page.</p>
                                        </div>
                                        <button class="btn btn-primary d-block pt-1 pb-1 mt-3" :disabled="is_processing" @click="redirectCoupons()">Add coupons</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-6">
                            <div class="item discount">
                                <div class="card shadow-sm mb-4">
                                    <div class="thumbnail">
                                        <img src="/images/ico-next-step-04.png"/>
                                    </div>
                                    <div class="information">
                                        <h6>Discount</h6>
                                        <div class="text">
                                            <p>Customers will get a discount automatically in their cart.</p>
                                        </div>
                                        <button class="btn btn-primary d-block pt-1 pb-1 mt-3" :disabled="is_processing" @click="redirectDiscount()">Add discount</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Multiselect from 'vue-multiselect'
import ShopTheme from './ShopTheme'
import GetTextColor from '../../mixins/GetTextColor'
import VueTimepicker from 'vue2-timepicker';

import 'vue2-timepicker/dist/VueTimepicker.css';

export default {
    name: 'ShopDashboard',
    components: {
        ShopTheme,
        VueTimepicker,
        Multiselect
    },
    mixins: [
        GetTextColor
    ],
    props: {
        business_id: String,
        business: Object,
        customisation: Object,
        shop_url: String,
        data: Object,
        refiner_survey_key: String,
    },
    watch: {
        'shipping.slots': {
            handler(values) {
                if(!values)
                    return;

                if (values.length > 0) {
                    this.errors.slots = '';
                }
                _.forEach(values, function (value) {
                    if (value.times.from != '' && value.times.to != '') {

                        let from = "01/01/2011 " + value.times.from;
                        let to = "01/01/2011 " + value.times.to;
                        let fromDate = new Date(Date.parse(from));
                        let toDate = new Date(Date.parse(to));

                        if (fromDate > toDate) {
                            value.error = "'To' time cannot be earlier than 'from' time"
                        } else {
                            value.error = '';
                        }
                    }
                });
            },
            deep: true
        },

        'pickup.slots': {
            handler(values) {
                if (values.length > 0) {
                    this.errors.slots = '';
                }
                _.forEach(values, function (value) {
                    if (value.times.from != '' && value.times.to != '') {

                        let from = "01/01/2011 " + value.times.from;
                        let to = "01/01/2011 " + value.times.to;
                        let fromDate = new Date(Date.parse(from));
                        let toDate = new Date(Date.parse(to));

                        if (fromDate > toDate) {
                            value.error = "'To' time cannot be earlier than 'from' time"
                        } else {
                            value.error = '';
                        }
                    }
                });
            },
            deep: true
        },

        'shipping.method_name': function(val) {
            this.errors.method_name = val.length > 0 ? null : 'The name can\'t be empty';
        },

        'shipping.rate': function(val) {
            this.errors.rate = val.length > 0 ? null : 'The amount can\'t be empty';
        },
        variations: {
            handler(values) {
                let arrays = {};

                _.forEach(values, function (value) {
                    if (value.value.length > 0) {
                        _.forEach(value, function (v) {
                            arrays[value.key] = v;
                        });
                    }
                });

                let result = [[]];

                _.forEach(arrays, function (property_values, property) {
                    let resultAlternative = [];

                    _.forEach(result, function (result_item) {
                        _.forEach(property_values, function (property_value) {
                            let new_result = _.assign([], result_item.values);

                            if (new_result !== undefined && new_result.constructor !== Array) {
                                new_result = [];
                            }

                            let new_value = {
                                key: property,
                                value: property_value,
                            };

                            new_result.push(new_value);

                            resultAlternative.push(_.assign({}, {
                                values: new_result,
                            }));
                        });
                    });

                    result = resultAlternative;
                });

                let temporaryResult = [];
                let temporary_form = this.product_option.variations;

                _.forEach(result, (value, key) => {
                    if (value !== undefined && value.constructor !== Array) {
                        let new_key = value.values.map((elem) => {
                            return elem.value;
                        }).join('_');

                        let current_one = undefined;

                        if (temporary_form !== undefined && temporary_form.constructor === Array) {
                            current_one = temporary_form.find(x => x.id === new_key);
                        }

                        if (current_one === undefined) {
                            temporaryResult.push(_.assign({}, value, {
                                id: new_key,
                                price: this.product.price,
                                quantity: '0',
                                quantity_alert_level: '',
                            }));
                        } else {
                            if (current_one.price === '') {
                                current_one.price = this.product.price;
                            }

                            temporaryResult.push(_.assign({}, value, current_one));
                        }
                    }
                });

                // do something like remove empty array inside
                // this.form.variations = result;
                this.product_option.variations = temporaryResult;
            },
            deep: true
        },
    },
    data() {
        return {
            is_hide_get_started: false,
            is_store_url: 0,
            is_theme: 0,
            is_products: 0,
            is_shipping_and_pickup: 0,
            is_ready : 0,
            step: 0,
            store_url: "",
            has_order: 0,
            store_url_full: "http://sanbox.myshop.com/mystore.official.page.com",
            product: {
                name: "",
                price: "",
                images: [],
                images_name: [],
                imageLimit: 6,
            },
            product_setting: {
                currency: "SDG"
            },
            is_add_option: false,
            product_option: {
                description: "",
                featured: false,
                has_variation: false,
                variations: [],
                categories: [],
                is_manageable: false,
                quantity: "0",
                quantity_alert_level: "0",
                is_variation_manageable: false,
                variation_errors: {},
            },
            variations: [],
            variation_errors: {},
            shipping_type: {
                shipping: false,
                pickup: false,
            },
            allCategories: [],
            shipping: {
                country: "",
                calculation: '',
                method_name: '',
                rate: '',
                description: "",
                currency: "SGD",
                has_slots: false,
                slots: [],
            },
            pickup: {
                street:"",
                city:"",
                state: '',
                postal_code: '',
                country: "",
                has_slots: false,
                slots: []
            },
            calculations: [],
            countries: [],
            errors: {},
            is_processing: false,
            is_succeeded: false,
            themes: [
                {
                    title: 'Default',
                    value: 'hitpay',
                    custom: false,
                    leftPanelBack: '#011B5F',
                    leftPanelFore: 'white',
                    leftPanelFore2: 'white',
                    buttonBack: '#011B5F',
                    buttonFore: 'white'
                },
                {
                    title: 'Light',
                    value: 'light',
                    custom: false,
                    leftPanelBack: 'white',
                    leftPanelFore: 'black',
                    leftPanelFore2: '#545454',
                    buttonBack: '#011B5F',
                    buttonFore: 'white'
                },
                this.getCustomTheme(this.customisation.tint_color)
            ],
            theme: this.customisation.theme,
            customColor: this.customisation.tint_color,
            usable_data: {
                week_days_list: {
                    Monday: 'Monday',
                    Tuesday: 'Tuesday',
                    Wednesday: 'Wednesday',
                    Thursday: 'Thursday',
                    Friday: 'Friday',
                    Saturday: 'Saturday',
                    Sunday: 'Sunday',
                },
                variation_list: {
                    Size: 'Size',
                    Color: 'Color',
                    Material: 'Material',
                }
            },
          product_success_message: false,
        };
    },
    mounted() {
        this.countries = this.data.countries;
        this.calculations = this.data.calculations;
        this.store_url = this.business.identifier;
        this.allCategories = this.data.categories;

        // add shipping
        this.shipping.calculation = this.calculations[0]['code'];
        this.shipping.country = this.business.country;

        this.product_setting.currency = this.business.currency.toUpperCase();

        this.pickup.street = this.business.street;
        this.pickup.city = this.business.city;
        this.pickup.state = this.business.state;
        this.pickup.postal_code = this.business.postal_code;

        this.getStartedStatus();
    },
    methods: {
        disallowDecimal(event) {
            if (event.keyCode === 190) {
                event.preventDefault();
            }
        },
        triggerSurvey(){
            _refiner('showForm', this.refiner_survey_key);
        },
        getStartedStatus(){
            this.is_processing = true;
            axios.get(this.getDomain(`v1/business/${this.business_id}/store-onboarding-status`, 'api'), {
                withCredentials: true
            }).then(response => {
                let data_get_started = response.data.get_started;
                this.has_order = response.data.has_order;

                if(this.has_order > 0 || response.data.get_started.hide_get_started){
                    this.is_hide_get_started = true;
                }

                this.setStep(data_get_started);

                this.is_processing = false;
            });
        },
        clickDismiss() {
          this.is_processing = true;
          this.is_hide_get_started = true;
          this.saveStatusGetStarted("hide_get_started", 1);
        },
        clickShowGetStarted() {
          this.is_processing = true;
          this.is_hide_get_started = false;
          this.saveStatusGetStarted("hide_get_started", 0);
        },
        saveStoreUrl() {
            this.is_processing = true;
            this.errors = {};
            
            if(!this.store_url || this.store_url.length == 0 ){
                this.errors.store_url = 'The store url is required.';
            }else if(! /(^[A-Za-z0-9-]+$)+/.test(this.store_url)) {
                this.errors.store_url = 'Only chars and digits are allowed in name'
            }

            if (Object.keys(this.errors).length > 0) {
                this.showError(_.first(Object.keys(this.errors)));
                this.is_processing = false;
                return;
            }

            axios.put(this.getDomain(`v1/business/${this.business_id}/shop-url`, 'api'), {
                identifier: this.store_url
            },{ withCredentials: true }).then(response => {
                this.is_processing = false;
                this.business.identifier = this.store_url;
                this.saveStatusGetStarted("store_url", 1);
                this.postHogOnlyCaptureData('store_url', '');
            }).catch(({response}) => {
                if (response.status === 422) {
                    this.is_processing = false;

                    _.forEach(response.data.errors, (value, key) => {
                        this.errors.store_url = _.first(value);
                        return false;
                    });
                }
            });
        },
        saveStatusGetStarted(key, value) {
            axios.put(this.getDomain(`v1/business/${this.business_id}/store-onboarding-status`, 'api'), {
                key: key,
                value: value
            },{ withCredentials: true }
            ).then(response => {
                this.is_processing = false;
                this.setStep(response.data.get_started);
            });
        },
        setStep(data_get_started){
            if (this.step !== 4 && this.is_ready)
                this.step +=1;

            else if(data_get_started.store_url == 0)
                this.step = 0;

            else if(data_get_started.theme == 0)
                this.step = 1;

            else if(data_get_started.products == 0)
                this.step = 2;

            else if(data_get_started.shipping_and_pickup == 0)
                this.step = 3;

            else if(data_get_started.shipping_and_pickup == 1 || data_get_started.hide_get_started == 1) {
                this.step = 4
                this.is_ready = 1;
            }

            this.is_store_url = data_get_started.store_url;
            this.is_theme = data_get_started.theme;
            this.is_products = data_get_started.products;
            this.is_shipping_and_pickup = data_get_started.shipping_and_pickup;
        },
        forceStep (step) {
            if(step == 4) {
                this.validateShippingPickup();
                if (Object.keys(this.errors).length > 0) {
                    this.showError(_.first(Object.keys(this.errors)));
                    this.is_processing = false;
                    return;
                }
            }

            this.step = step;
        },
        getBack(page){
          this.is_processing = true;

          this.saveStatusGetStarted(page, 0);
        },
        saveTheme() {
            this.is_processing = true;

            axios.put(this.getDomain(`v1/business/${this.business_id}/theme-customisation`, 'api'), {
                theme: this.theme,
                customColor: this.customColor
            },{ withCredentials: true }).then(response => {
                this.saveStatusGetStarted("theme", 1);
                this.postHogOnlyCaptureData('theme', '');
            });
        },
        triggerSlots(event, status) {
            event.preventDefault();
            this.shipping.has_slots = status;
        },
        triggerPickupSlots(event, status) {
            event.preventDefault();
            this.pickup.has_slots = status;
        },
        triggerVariation(event, status) {
            event.preventDefault();

            this.product_option.has_variation = status;

            if (this.product_option.has_variation) {
                this.product_option.is_manageable = false;
                this.product_option.quantity = '';
                this.product_option.quantity_alert_level = '';
            } else {
                this.product_option.variations = [];
            }
        },
        publishProduct(isPublish){
            let loading = $('<span>').html('<i class="fas fa-spinner fa-spin"></i>');

            $('#publish_product').append(' '.loading);

            this.errors = {};
            this.is_processing = true;

            if (!this.product.name) {
                this.errors.name = 'The name field is required.';
            } else if (this.product.name.length >= 255) {
                this.errors.name = 'The name may not be greater than 255 characters.';
            }

            if (!this.product.price) {
                this.errors.product_price = 'The selling price field is required.';
            } else if (isNaN(this.product.price)) {
                this.errors.product_price = 'The selling price must be a number.';
            } else if (this.product.price < 1) {
                this.errors.product_price = 'The price can\'t be lower than $1.';
            } else if (this.product.price > 99999999999999) {
                this.errors.product_price = 'The price can\'t be greater than $99999999999999.';
            }

            if (Object.keys(this.errors).length > 0) {
                this.showError(_.first(Object.keys(this.errors)));
                this.is_processing = false;
            }

            if (this.is_add_option && !this.product_option.has_variation && this.product_option.is_manageable) {
                if (!this.product_option.quantity) {
                    this.errors.available_quantity = 'The quantity field is required when the product inventory is tracked.';
                } else if (isNaN(this.product_option.quantity)) {
                    this.errors.available_quantity = 'The quantity must be a number.';
                }

                if (this.product_option.quantity_alert_level.length && isNaN(this.product_option.quantity_alert_level)) {
                    this.errors.quantity_alert = 'The quantity alert must be a number.';
                }
            }

            if (Object.keys(this.errors).length > 0
                || (this.product_option.has_variation && this.product_option.variations.length === 0)
                || Object.keys(this.variation_errors).length > 0) {
                this.is_processing = false;
                return;
            }

            let form = new FormData();
            form.append('name', this.product.name);
            form.append('price', this.product.price);
            form.append('description', this.product_option.description);
            form.append('is_pinned', this.product_option.featured ? 1 : 0)
            form.append('publish', isPublish === true ? 1 : 0);
            form.append('currency', this.business.currency);

            var product_categories = [];
            _.each(this.product_option.categories, function (category, key) {
                product_categories.push(category.id);
            });

            form.append('business_product_category_id', JSON.stringify(product_categories));

            if (!this.product_option.is_manageable && this.product_option.has_variation && this.product_option.variations.length > 0) {
                form.append('is_manageable', this.product_option.is_variation_manageable ? 1 : 0);

                let i = 0;

                _.forEach(this.product_option.variations, (value) => {
                    let j = 0;

                    _.forEach(value.values, function (v) {
                        form.append('variation[' + i + '][values][' + j + '][key]', v.key);
                        form.append('variation[' + i + '][values][' + j + '][value]', v.value);
                        j++;
                    });

                    if (this.product_option.is_variation_manageable) {
                        form.append('variation[' + i + '][quantity]', parseInt(value.quantity));
                        form.append('variation[' + i + '][quantity_alert_level]', parseInt(value.quantity_alert_level));
                    }

                    form.append('variation[' + i + '][price]', value.price);

                    i++;
                });
            } else {
                form.append('is_manageable', this.product_option.is_manageable ? 1 : 0);
                form.append('quantity', parseInt(this.product_option.quantity));
                form.append('quantity_alert_level', parseInt(this.product_option.quantity_alert_level));
            }

            if ((this.product.images).length > 0) {
                let i = 1;
                var self = this;
                _.forEach(this.product.images, function (image) {
                    form.append('image[' + i + ']', self.dataURItoBlob(image), i + '.jpg');
                    i++;
                });
            }

            axios.post(this.getDomain(`v1/business/${this.business_id}/product`, 'api'), form, {
                headers: { 'Accept': 'application/json' },
                withCredentials: true
            }).then(response => {
                this.resetProduct();
                this.product_success_message = true;

                // Hide message success
                setTimeout(() => {
                    this.product_success_message = false;
                }, 5000);
                this.is_processing = false;
            });
        },
        resetProduct() {
            this.product.name = "";
            this.product.price = "";
            this.product.images = [];
            this.product.images_name = [];

            if(this.is_add_option) {
                this.product_option.description = "";
                this.product_option.featured = false,
                this.product_option.has_variation = false;
                this.product_option.variations = [];
                this.product_option.is_manageable = false;
                this.product_option.quantity = "0";
                this.product_option.quantity_alert_level = "0";
                this.product_option.is_variation_manageable = false;
                this.product_option.variation_errors= {};
                this.variations = [];
                this.variation_errors = {};
                this.is_add_option = false;
            }
        },
        dataURItoBlob(dataURI) {
            var byteString;

            if (dataURI.split(',')[0].indexOf('base64') >= 0) {
                byteString = atob(dataURI.split(',')[1]);
            } else {
                byteString = unescape(dataURI.split(',')[1]);
            }

            var mimeString = dataURI.split(',')[0].split(':')[1].split(';')[0];

            var ia = new Uint8Array(byteString.length);

            for (var i = 0; i < byteString.length; i++) {
                ia[i] = byteString.charCodeAt(i);
            }

            return new Blob([ia], {type: mimeString});
        },
        saveProduct() {
            this.is_processing = true;
            this.saveStatusGetStarted("products", 1);
            this.postHogOnlyCaptureData('products', '');
        },
        saveShipping() {
            this.is_processing = true;
            
            this.validateShippingPickup();

            if (Object.keys(this.errors).length > 0) {
                this.showError(_.first(Object.keys(this.errors)));
                this.is_processing = false;
                return;
            }

            let can_pick_up = false;
            if(this.shipping_type.pickup) {
                can_pick_up = true;
            }

            axios.post(this.getDomain(`v1/business/${this.business_id}/shipping`, 'api'), {
                name: this.shipping.method_name,
                calculation: this.shipping.calculation,
                description: this.shipping.description,
                active: 1,
                rate: this.shipping.rate,
                country: this.shipping.country,
                slots: this.shipping.slots
            },{ withCredentials: true }).then(response => {
                console.log(response);
            });

            axios.put(this.getDomain(`v1/business/${this.business_id}/shipping/enable`, 'api'), {
                enabled_shipping: this.shipping_type.shipping
            },{ withCredentials: true }).then(response => {
                console.log(response);
            });

            axios.put(this.getDomain(`v1/business/${this.business_id}/pick-up`, 'api'), {
                can_pick_up: can_pick_up,
                slots: this.pickup.slots,
                street: this.pickup.street,
                city: this.pickup.city,
                state: this.pickup.state,
                postal_code: this.pickup.postal_code,
                },{ withCredentials: true }).then(response => {
                    console.log(response);
                });

            this.saveStatusGetStarted("shipping_and_pickup", 1);
            this.postHogOnlyCaptureData('shipping_and_pickup', '');
            this.postHogOnlyCaptureData('shop is ready', '');
        },
        validateShippingPickup() {
            this.errors = {};
            if(this.shipping_type.shipping && this.business.shippings_count <=1) {
                if (this.shipping.country === '') {
                    this.errors.country = 'The country is invalid';
                }

                let country = this.countries.find(x => x.code === this.shipping.country);

                if (country === undefined) {
                    this.errors.country = 'The country is invalid';
                }

                if (this.shipping.calculation === '') {
                    this.errors.calculation = 'The calculation is invalid';
                }

                if (this.shipping.method_name === '') {
                    this.errors.method_name = 'The name can\'t be empty';
                }

                if (this.shipping.rate === '') {
                    this.errors.rate = 'The amount can\'t be empty';
                }

                if(Number.parseInt(this.shipping.rate) < 0 || Number.parseInt(this.shipping.rate) > 9999999){
                    this.errors.rate = 'The amount must be from 0 to 9999999';
                }

                let decimalLength = 0;
                let split = this.shipping.rate.split(".");

                if (split.length > 1) {
                    decimalLength = split[1].length || 0;
                }

                if (decimalLength > 2) {
                    this.is_processing = false;
                    return this.errors.rate = 'The amount can\'t have more than 2 decimals.';
                }

                if (this.shipping.has_slots) {
                    if (this.shipping.slots.length > 0) {
                        let err = false;
                        _.each(this.shipping.slots, value => {
                            if (value.error != '') {
                                err = true;
                                return;
                            }
                            if (value.times.from === '' || value.times.to === '') {
                                err = true;
                                value.error = "Please fill in both times";
                                return;
                            }
                        });
                        if (err) {
                            this.is_processing = false;
                            return;
                        }
                        this.shipping.slots = JSON.stringify(this.shipping.slots);
                    } else {
                        this.errors.slots = 'Please choose date and time slots for your delivery';
                    }
                } else {
                    this.shipping.slots = null;
                }
            }

            if(this.shipping_type.pickup) {
                if(!this.pickup.street || this.pickup.street === '') {
                    this.errors.pickup_street = "The street can't be empty";
                }

                if(!this.pickup.city || this.pickup.city === '') {
                    this.errors.city = "The city can't be empty";
                }

                if(!this.pickup.state || this.pickup.state === '') {
                    this.errors.state = "The state can't be empty";
                }

                if(!this.pickup.postal_code || this.pickup.postal_code === '') {
                    this.errors.postal_code = "The postal code can't be empty";
                }
                if(!this.pickup.country || this.pickup.country === '') {
                    this.errors.pickup_country = "The country can't be empty";
                }
            }
        },
        addOptions() {
            this.is_add_option = true;
            this.product_option.is_manageable = true;
        },
        redirecProductList() {
            window.location.href = this.getDomain(`business/${ this.business_id }/product`, "dashboard");
        },
        redirectCategory() {
            window.location.href = this.getDomain(`business/${ this.business_id }/product-categories`, "dashboard");
        },
        redirectCoupons() {
            window.location.href = this.getDomain(`business/${ this.business_id }/coupon`, "dashboard");
        },
        redirectStoreSetting() {
            window.location.href = this.getDomain(`business/${ this.business_id }/setting/shop`, "dashboard");
        },
        redirectDiscount() {
            window.location.href = this.getDomain(`business/${ this.business_id }/discount`, "dashboard");
        },
        showError(firstErrorKey) {
            if (firstErrorKey !== undefined) {
                this.scrollTo('#' + firstErrorKey);

                $('#' + firstErrorKey).focus();
            }
        },
        getCustomTheme (color) {
            return {
                title: 'Custom',
                value: 'custom',
                custom: true,
                leftPanelBack: color,
                leftPanelFore: this.getTextColor(color),
                leftPanelFore2: this.getTextColor(color),
                buttonBack: color,
                buttonFore: this.getTextColor(color)
            }
        },
        onChangeCustomColor (color) {
            this.customColor = color

            // replace custom theme object
            const idx = this.themes.findIndex(t => t.value === 'custom')
            this.$set(this.themes, idx, this.getCustomTheme(color))
        },
        addImage(event) {
            event.preventDefault();

            event.target.files.forEach((file) => {
                const max = 1600;
                const reader = new FileReader();

                this.product.images_name.push(file.name);

                reader.readAsDataURL(file);
                reader.onload = event => {
                    const img = new Image();
                    img.src = event.target.result;
                    img.onload = () => {
                        const elem = document.createElement('canvas');

                        let ctx;
                        let width;
                        let height;

                        if (img.width > img.height) {
                            const scaleFactor = max / img.width;

                            elem.width = max;
                            elem.height = img.height * scaleFactor;

                            width = max;
                            height = img.height * scaleFactor;
                        } else {
                            const scaleFactor = max / img.height;

                            elem.width = img.width * scaleFactor;
                            elem.height = max;

                            width = img.width * scaleFactor;
                            height = max;
                        }

                        ctx = elem.getContext('2d');
                        ctx.drawImage(img, 0, 0, width, height);

                        this.product.images.push(ctx.canvas.toDataURL("image/jpeg", 0.7));
                    };
                };

                reader.onerror = error => console.log(error);
            });
        },
        removeImage(event, key) {
            event.preventDefault();

            (this.product.images).splice(key, 1);
            (this.product.images_name).splice(key, 1);
        },
        addSlot(event, key) {
            event.preventDefault();

            if (this.shipping.slots.length < 7) {
                this.shipping.slots.push({
                    day: key,
                    times: {
                        from: '',
                        to: '',
                    },
                    error: ''
                });
                let slots = this.shipping.slots;
                this.shipping.slots = this.orderSlot(slots);
            }
        },
        addPickupSlot(event, key) {
            event.preventDefault();

            if (this.pickup.slots.length < 7) {
                this.pickup.slots.push({
                    day: key,
                    times: {
                        from: '',
                        to: '',
                    },
                    error: ''
                });
                let slots = this.pickup.slots;
                this.pickup.slots = this.orderSlot(slots);
            }
        },
        orderSlot(slots) {
            let arr = ['', '', '', '', '', '', '']
            slots.forEach(day => {
                if (day.day === 'Monday') arr[0] = day
                if (day.day === 'Tuesday')  arr[1] = day
                if (day.day === 'Wednesday') arr[2] = day
                if (day.day === 'Thursday') arr[3] = day
                if (day.day === 'Friday') arr[4] = day
                if (day.day === 'Saturday') arr[5] = day
                if (day.day === 'Sunday') arr[6] = day
            });

            return arr.filter(str => str !== '')
        },
        removeSlot(event, key) {
            event.preventDefault();
            let slot = this.shipping.slots.indexOf(this.shipping.slots.find(x => x.day === key));
            this.shipping.slots.splice(slot, 1);
        },
        removePickupSlot(event, key) {
            event.preventDefault();
            let slot = this.pickup.slots.indexOf(this.pickup.slots.find(x => x.day === key));
            this.pickup.slots.splice(slot, 1);
        },
        removeVariant(event, key) {
            event.preventDefault();
            let variant = this.product_option.variations.indexOf(this.product_option.variations.find(x => x.key === key));
            this.product_option.variations.splice(variant, 1);
        },
        removeVariantChild(event, index, list) {
            event.preventDefault();
            list.splice(index, 1);
        },
        addVariation(event, key) {
            event.preventDefault();

            if (this.variations.length < 3) {
                this.variations.push({
                    key: key,
                    value: [
                        //
                    ],
                });
            }
        },
        appendListWhenBlur(event, variation) {
            if (event.target.value.length > 0) {
                variation.value.push(event.target.value);
                event.target.value = null;
            }
        },
        appendList(event, variation) {
            if (event.target.value.length > 0 && (event.keyCode === 13)) {
                if (this.product_option.variations.length >= 100) {
                    console.log('terlalu banyak');
                } else if (variation.value.find(function (value) {
                    return value === event.target.value;
                })) {
                } else {
                    variation.value.push(event.target.value);
                    event.target.value = null;
                }
            } else if (event.keyCode === 8 && event.target.value.length === 0) {
                let previousValue = variation.value.pop();

                if (previousValue !== undefined) {
                    event.preventDefault();
                    event.target.value = previousValue;
                }
            }
        },
        redirectStore() {
            window.open(this.shop_url + this.business.identifier, '_blank');
        },
        copyStoreUrl() {
            this.$clipboard(this.shop_url + this.business.identifier);
            alert('Copied to clipboard');
        },
        removeVariant(event, key) {
            event.preventDefault();

            let variant = this.variations.indexOf(this.variations.find(x => x.key === key));

            this.variations.splice(variant, 1);
        },
        changeUrl(value){
            if(value.includes(' ')){
                this.store_url = value.replace(' ', '-');
            }
        }
    }
}
</script>
