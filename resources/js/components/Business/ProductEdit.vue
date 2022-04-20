<style scoped>
.fake-form-control {
    padding: 6px 12px;
    border: 1px solid #ced4da;
}

.inline-input:focus {
    outline: none;
}

.special .list-inline-item {
    margin-right: 0.25rem;
}

.special .list-inline-item:not(:last-child):after {
    content: '·';
    color: #495057;
    margin-left: 0.25rem;
}

.special .list-inline-item:last-child {
    margin-right: 0;
}

.border-alert-danger {
    border-color: #f5c6cb !important;
}

::placeholder {
    color: #cecece;
}
</style>
<template>
    <div>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-4">
                <h3 class="font-weight-bold mb-4">{{ product.name }} <span v-if="product.is_shopify" class="float-right"><img src="/images/shopify.svg" alt="shopify"></span></h3>

                <div class="mb-4">
                    <p class="mb-2">Product Checkout URL</p>
                    <div class="input-group mb-1">
                        <input id="copyTarget" type="text"
                               class="form-control form-control-sm bg-light font-weight-bold" v-model="checkout_url"
                               title="Checkout Link" disabled>
                        <div class="input-group-append">
                            <button id="copyButton" class="btn btn-outline-primary btn-sm" @click="copy">Copy</button>
                        </div>
                        <div class="input-group-append">
                            <a class="btn btn-outline-primary btn-sm" :href="product.checkout_url"
                               target="_blank">View</a>
                        </div>
                    </div>
                    <div class="text-center">
                    </div>
                </div>
                <div class="form-group">
                    <label>Product Name <span class="text-danger">*</span></label>
                    <input type="text" v-model="product.name" :class="{ 'is-invalid' : errors.product.name }"
                           class="form-control bg-light" title="Product Name" placeholder="T-Shirt">
                    <span v-if="errors.product.name" class="invalid-feedback" role="alert">{{
                            errors.product.name
                        }}</span>
                </div>
                <div class="form-group">
                    <label>SKU</label>
                    <input type="text" v-model="product.stock_keeping_unit" :class="{ 'is-invalid' : errors.product.stock_keeping_unit }"
                           class="form-control bg-light" title="SKU" placeholder="SKU">
                    <span v-if="errors.product.stock_keeping_unit" class="invalid-feedback" role="alert">{{
                            errors.product.stock_keeping_unit
                        }}</span>
                </div>
                <div class="form-group">
                    <label>Selling Price <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon3">{{ display_currency }}</span>
                        </div>
                        <input v-model="product.price" :class="{ 'is-invalid' : errors.product.price }"
                               class="form-control bg-light" placeholder="10.00" step="0.01" title="Selling Price" @keyup="changeProductPrice">
                    </div>
                    <span v-if="errors.product.price" class="d-block small text-danger w-100 mt-1" role="alert">
                        {{ errors.product.price }}
                    </span>
                </div>
                <div class="form-group mb-0">
                    <label>Description</label>
                    <textarea v-model="product.description" :class="{ 'is-invalid' : errors.product.description }"
                              class="form-control bg-light" rows="4" title="Description"
                              placeholder="A T-shirt is a style of unisex fabric shirt named after the T shape of its body and sleeves. Traditionally it has short sleeves and a round neckline, known as a crew neck, which lacks a collar…"></textarea>
                    <span v-if="errors.product.description" class="invalid-feedback"
                          role="alert">{{ errors.product.description }}</span>
                </div>
            </div>
            <div v-if="product.variations_count <= 1 && form_control.has_variation === false"
                 class="card-body p-4 border-top">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="isManageable"
                           v-model="product.is_manageable">
                    <label class="custom-control-label" for="isManageable">Manage Inventory</label>
                </div>
                <div v-if="product.is_manageable" class="mt-3">
                    <div class="form-row">
                        <div class="form-group col-12 col-sm-6 mb-sm-0">
                            <label>Available Quantity <span class="text-danger">*</span></label>
                            <input v-model="product.quantity" :class="{ 'is-invalid' : errors.product.quantity }"
                                   class="form-control bg-light" title="Available Quantity" placeholder="10"
                                   @keydown="disallowDecimal($event)">
                            <span v-if="errors.product.quantity" class="invalid-feedback"
                                  role="alert">{{ errors.product.quantity }}</span>
                        </div>
                        <div class="form-group col-12 col-sm-6 mb-0">
                            <label>Low Quantity Alert</label>
                            <input v-model="product.quantity_alert_level" :class="[
                                {
                                    'is-invalid' : errors.product.quantity_alert_level
                                },
                                {
                                    'bg-light': product.quantity !== '',
                                }
                            ]" class="form-control" title="Low Quantity Alert" placeholder="3"
                                   :disabled="product.quantity === ''"
                                   @keydown="disallowDecimal($event)">
                            <span v-if="errors.product.quantity_alert_level" class="invalid-feedback"
                                  role="alert">{{ errors.product.quantity_alert_level }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body border-top">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="isPinned"
                           v-model="product.is_pinned">
                    <label class="custom-control-label" for="isPinned">Featured Products</label>
                </div>
            </div>
        </div>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body border-top">
                <h5 class="font-weight-bold mb-3">Product Categories</h5>
                <multiselect v-model="product.categories" :options="allCategories" :multiple="true"
                             :close-on-select="true"
                             :clear-on-select="false" placeholder="Choose categories" label="name"
                             track-by="name" :max="5"></multiselect>
            </div>
        </div>
        <div class="card border-0 shadow-sm mb-3" v-if="product.variations_count > 1">
            <div class="card-body p-4">
                <h5 class="font-weight-bold mb-3">Variants</h5>
                <p class="mb-0">Add variants if this product comes in multiple versions, like different sizes or
                    colors.</p>
                <div v-if="product.variations.length > 0" class="custom-control custom-checkbox mt-3">
                    <input type="checkbox" class="custom-control-input" id="isVariationManageable"
                           v-model="product.is_manageable">
                    <label class="custom-control-label" for="isVariationManageable">I want to manage inventory by
                        individual variants</label>
                </div>
            </div>
            <table v-if="product.variations.length > 0" class="table table-hover border-bottom mb-0">
                <tr class="bg-light">
                    <th scope="col">Variants</th>
                    <th scope="col">Selling Price</th>
                    <th scope="col" v-if="product.is_manageable">Quantity</th>
                    <th scope="col" v-if="product.is_manageable">Low Quantity Alert</th>
                </tr>
                <template v-for="(item, key) in product.variations">
                    <tr :class="{
                        'alert-danger': errors.variations[item.id]
                    }">
                        <td>
                            <div class="col-form-label">
                                <ul class="special list-inline d-inline mb-0">
                                    <li v-if="v.key" v-for="(v, i) in item.values">{{ v.key }} : {{ v.value }}</li>
                                </ul>
                                <a class="text-danger font-weight-bold" href="#"
                                   @click="showDeleteVariant($event, item)">Delete</a>
                            </div>
                        </td>
                        <td>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">{{ display_currency }}</span>
                                </div>
                                <input v-model="item.price" title="Selling Price" class="form-control"
                                       :placeholder="product.price" step="0.01">
                            </div>
                        </td>
                        <td v-if="product.is_manageable">
                            <input v-model="item.quantity" title="Quantity" class="form-control"
                                   @keydown="disallowDecimal($event)">
                        </td>
                        <td v-if="product.is_manageable">
                            <input v-model="item.quantity_alert_level" title="Quantity" class="form-control"
                                   :disabled="item.quantity === ''" @keydown="disallowDecimal($event)">
                        </td>
                    </tr>
                    <tr v-if="errors.variations[item.id]">
                        <td :colspan="product.is_manageable ? 6 : 4"
                            class="alert-danger border-left-0 border-right-0 border-bottom-0 border-alert-danger rounded-0">
                            <ul class="list-unstyled small mb-0">
                                <li v-for="error in errors.variations[item.id]">
                                    <i class="fas fa-caret-right fa-fw"></i> {{ error }}
                                </li>
                            </ul>
                        </td>
                    </tr>
                </template>
            </table>
            <div class="card-body p-4">
                <button class="btn btn-secondary btn-block" href="#" data-toggle="modal"
                        data-target="#addNewVariationModal">Add a new variation
                </button>
            </div>
        </div>
        <div v-else class="card border-0 shadow-sm mb-3">
            <div class="card-body border-top">
                <a :class="{ 'text-danger' : form_control.has_variation }"
                   @click="triggerVariation($event, !form_control.has_variation)"
                   href="#" class="float-right small">
                    {{ form_control.has_variation ? 'Disable variants' : 'Enable variants' }}
                </a>
                <h5 class="font-weight-bold mb-3">Variants</h5>
                <p class="mb-0">Add variants such as size and color</p>
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
                                       class="inline-input border-0 w-100 bg-light p-0" title="value">
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="form_control.has_variation" :class="{ 'mt-3' : variations.length < 3 }">
                    <a v-for="(item, index) in usable_data.variation_list" v-if="!variations.find(x => x.key === index)"
                       @click="addNewVariation($event, index)" class="btn btn-outline-secondary btn-sm mr-1" href="#">
                        + {{ item }}
                    </a>
                </div>
                <div v-if="product.new_variations.length > 0" class="custom-control custom-checkbox mt-3">
                    <input type="checkbox" class="custom-control-input" id="isVariationManageable"
                           v-model="form_control.is_variation_manageable">
                    <label class="custom-control-label" for="isVariationManageable">Manage Variants Inventory</label>
                </div>
            </div>
            <table v-if="form_control.has_variation && product.new_variations.length > 0"
                   class="table table-hover border-top mb-0">
                <tr class="bg-light">
                    <th scope="col">Variants</th>
                    <th scope="col">Selling Price</th>
                    <th scope="col" v-if="form_control.is_variation_manageable">Quantity</th>
                    <th scope="col" v-if="form_control.is_variation_manageable">Low Quantity Alert</th>
                </tr>
                <template v-for="(item, key) in product.new_variations">
                    <tr :class="{
                                'alert-danger': variation_errors[item.id]
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
                                    <span class="input-group-text">{{ display_currency }}</span>
                                </div>
                                <input v-model="item.price" title="Selling Price"
                                       class="form-control" :placeholder="product.price" step="0.01">
                            </div>
                        </td>
                        <td v-if="form_control.is_variation_manageable">
                            <input v-model="item.quantity" title="Quantity" class="form-control"
                                   @keydown="disallowDecimal($event)">
                        </td>
                        <td v-if="form_control.is_variation_manageable">
                            <input v-model="item.quantity_alert_level" title="Quantity" class="form-control"
                                   :class="{'bg-light': item.quantity !== null && item.quantity !== ''}"
                                   :disabled="item.quantity === null || item.quantity === ''"
                                   @keydown="disallowDecimal($event)">
                        </td>
                    </tr>
                    <tr v-if="variation_errors[item.id]">
                        <td :colspan="form_control.is_variation_manageable ? 5 : 3"
                            class="alert-danger border-left-0 border-right-0 border-bottom-0 border-alert-danger rounded-0">
                            <ul class="list-unstyled small mb-0">
                                <li v-for="error in variation_errors[item.id]">
                                    <i class="fas fa-caret-right fa-fw"></i> {{ error }}
                                </li>
                            </ul>
                        </td>
                    </tr>
                </template>
            </table>
        </div>
        <div class="card border-0 shadow-sm mb-3" v-if="!is_loading">
            <div class="card-body p-4">
                <h5 class="font-weight-bold mb-3">Images</h5>
                <small class="d-block">The optimal product image size is 600*600, 800*800 and 1000*1000.</small>
                <small>You can upload up to 6 images</small>
                <div v-if="(product.image).length < imageLimit">
                    <label class="d-inline-flex mb-1" for="productImage">
                        <input type="file" id="productImage" class="custom-file-input d-none" accept="image/*"
                               @change="handleImage($event)" multiple="multiple">
                        <span id="uploadBtn" class="btn btn-primary">
                        <i class="fas fa-folder-open"></i> Choose image
                    </span>
                    </label>
                </div>
                <template v-if="(product.image).length > 0">
                    <div class="row mt-4">
                        <template v-for="(image,key) in product.image">
                            <div class="col-md-4">
                        <span>
                            <a class="text-danger" href="#" @click.prevent="deleteImage(image.id, key)">Remove Image</a>
                        </span>
                                <img v-if="image.url" :src="image.url" :title="image.id" class="img-fluid rounded"
                                     style="max-width: 200px">
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>
        <button id="publishBtn" class="btn btn-success btn-lg btn-block mb-3 shadow-sm" @click="updateProduct(true)"
                :disabled="is_updating">
            {{ product.is_published ? 'Save' : 'Publish' }}
            <i class="fas fa-spin fa-spinner" :class="{ 'd-none' : !is_updating }"></i></button>
        <button id="saveBtn" class="btn btn-secondary btn-sm btn-block mb-3 shadow-sm" @click="updateProduct(false)"
                :disabled="is_updating">
            {{ product.is_published ? 'Unpublish & Save as Draft' : 'Save' }}
            <i class="fas fa-spin fa-spinner" :class="{ 'd-none' : !is_updating }"></i></button>
        <button id="duplicateBtn" class="btn btn-primary btn-sm btn-block mb-3 shadow-sm"
                @click="duplicateProduct(false)"
                :disabled="is_updating">Duplicate Product
            <i class="fas fa-spin fa-spinner" :class="{ 'd-none' : !is_updating }"></i></button>
        <div class="text-center">
            <a id="deleteBtn" class="text-danger" @click.prevent="deleteProduct" :disabled="is_updating" href="#">
                Delete <i class="fas fa-spin fa-spinner" :class="{ 'd-none' : !is_updating }"></i></a>
        </div>
        <p class="text-danger font-weight-bold text-center mb-0" v-if="Object.keys(errors.product).length > 0">Scroll up
            to review required fields to proceed</p>
        <div id="addNewVariationModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true"
             data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title font-weight-bold">Add new variation</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" v-if="!is_loading">
                        <div class="form-group" v-for="variation in product.variation_types">
                            <label>{{ usable_data.variation_list[variation] }}</label>
                            <input type="text" :title="usable_data.variation_list[variation]"
                                   class="form-control bg-light" v-model="control.temp_variation[variation]"
                                   :class="{ 'is-invalid' : errors.temp_variation[variation] }">
                            <span v-if="errors.temp_variation[variation]" class="invalid-feedback"
                                  role="alert">{{ errors.temp_variation[variation] }}</span>
                        </div>
                        <div class="form-group" :class="{ 'mb-0' : !product.is_manageable }">
                            <label>Selling Price</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"
                                          :class="{ 'border-danger' : errors.temp_variation.price }">{{
                                            display_currency
                                        }}</span>
                                </div>
                                <input title="Selling Price" class="form-control bg-light" :placeholder="product.price"
                                       step="0.01"
                                       v-model="control.temp_variation.price"
                                       :class="{ 'is-invalid' : errors.temp_variation.price }">
                            </div>
                            <span v-if="errors.temp_variation.price" class="small text-danger"
                                  role="alert">{{ errors.temp_variation.price }}</span>
                        </div>
                        <div v-if="product.is_manageable">
                            <div class="form-row mb-0">
                                <div class="form-group col-12 col-sm-6 mb-sm-0">
                                    <label>Available Quantity</label>
                                    <input v-model="control.temp_variation.quantity"
                                           :class="{ 'is-invalid' : errors.temp_variation.quantity }"
                                           class="form-control bg-light" title="Available Quantity" placeholder="10"
                                           @keydown="disallowDecimal($event)">
                                    <span v-if="errors.temp_variation.quantity" class="invalid-feedback"
                                          role="alert">{{ errors.temp_variation.quantity }}</span>
                                </div>
                                <div class="form-group col-12 col-sm-6 mb-0">
                                    <label>Low Quantity Alert</label>
                                    <input v-model="control.temp_variation.quantity_alert_level" :class="[
                                        {
                                            'is-invalid' : errors.temp_variation.quantity_alert_level
                                        },
                                        {
                                            'bg-light': control.temp_variation.quantity !== '',
                                        }
                                    ]" class="form-control" title="Low Quantity Alert" placeholder="3"
                                           :disabled="control.temp_variation.quantity === ''"
                                           @keydown="disallowDecimal($event)">
                                    <span v-if="errors.temp_variation.quantity_alert_level" class="invalid-feedback"
                                          role="alert">{{ errors.temp_variation.quantity_alert_level }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer border-top">
                        <button type="button" class="btn btn-primary" @click="addVariation">Add</button>
                    </div>
                </div>
            </div>
        </div>
        <div id="deleteVariationModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title font-weight-bold">Delete a variation</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" v-if="modal_variation !== null">
                        Are you sure you want to delete this variation
                        <strong>{{
                                modal_variation.values.map(function (elem) {
                                    return elem.value;
                                }).join(' ')
                            }}</strong>? This will delete the variation immediately and can't be undone.
                    </div>
                    <div class="card-footer">
                        <button type="button" class="btn btn-outline-danger" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" @click="deleteVariant($event)">Delete</button>
                    </div>
                </div>
            </div>
        </div>
        <div id="confirmDeleteModal" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-body">
                        <h5 class="modal-title text-danger font-weight-bold mb-0">
                            Are you sure you want to delete this product "{{ product.name }}"?
                        </h5>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" :disabled="is_updating">
                            Close
                        </button>
                        <button type="button" class="btn btn-danger" @click="confirmDeleteProduct"
                                :disabled="is_updating">
                            <i class="fas fa-times mr-1"></i> Confirm Delete <i v-if="is_updating"
                                                                                class="fas fa-spinner fa-spin"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div id="loadingModal" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-body py-5">
                        <h5 class="modal-title text-success font-weight-bold mb-0">
                            Saving… <i class="fas fa-spinner fa-spin"></i>
                        </h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Multiselect from 'vue-multiselect'

Vue.component('multiselect', Multiselect)
export default {
    components: {Multiselect},
    watch: {
        product: {
            handler(values) {
                if (this.is_loading) {
                    return;
                }

                if (values.price !== '') {
                    let indexOfPeriodForPrice = values.price.indexOf('.');
                    let decimalsLengthForPrice = values.price.substr(indexOfPeriodForPrice);

                    if (decimalsLengthForPrice && decimalsLengthForPrice.length > 3) {
                        this.errors.product.price = 'The price can\'t have more than two decimals.';
                    } else {
                        this.errors.product.price = null;
                    }
                }

                if (values.quantity) {
                    let checkForQuantity = values.quantity.match(/\./g);

                    if (checkForQuantity && checkForQuantity.length > 0) {
                        this.errors.product.quantity = 'The quantity can\'t have decimals.';
                    } else {
                        this.errors.product.quantity = null;
                    }

                    if (values.quantity_alert_level) {
                        let checkForQuantityAlertLevel = values.quantity_alert_level.match(/\./g);

                        if (checkForQuantityAlertLevel && checkForQuantityAlertLevel.length > 0) {
                            this.errors.product.quantity_alert_level = 'The alert level can\'t have decimals.';
                        } else {
                            this.errors.product.quantity_alert_level = null;
                        }
                    }
                } else if (values.quantity_alert_level) {
                    this.errors.product.quantity_alert_level = 'The quantity can\'t be empty if the alert level is set.';
                }

                let specialErrors = {};
                let manageable = values.is_manageable;

                _.forEach(values.variations, function (value) {
                    let errorForThis = [];

                    if (value.price) {
                        let indexOfPeriodForPrice = value.price.indexOf('.');
                        let decimalsLengthForPrice = value.price.substr(indexOfPeriodForPrice);

                        if (isNaN(value.price)) {
                            errorForThis.push('The price must be a number.');
                        } else if (decimalsLengthForPrice && decimalsLengthForPrice.length > 3) {
                            errorForThis.push('The price can\'t have more than two decimals.');
                        }
                    }

                    if (value.quantity === 'undefined') {
                        value.quantity = '0';
                    }

                    if (value.quantity_alert_level === 'undefined') {
                        value.quantity_alert_level = '0';
                    }

                    if (manageable && value.quantity) {
                        let checkForQuantity = value.quantity.match(/\./g);

                        if (isNaN(value.quantity)) {
                            errorForThis.push('The quantity must be a number.');
                        } else if (checkForQuantity && checkForQuantity.length > 0) {
                            errorForThis.push('The quantity can\'t have decimals.');
                        }

                        if (value.quantity_alert_level) {
                            let checkForQuantityAlertLevel = value.quantity_alert_level.match(/\./g);

                            if (isNaN(value.quantity_alert_level)) {
                                errorForThis.push('The alert level must be a number.');
                            } else if (checkForQuantityAlertLevel && checkForQuantityAlertLevel.length > 0) {
                                errorForThis.push('The alert level can\'t have decimals.');
                            }
                        }
                    } else if (value.quantity_alert_level) {
                        value.quantity_alert_level = null;
                    }

                    if (errorForThis.length > 0) {
                        specialErrors[value.id] = errorForThis;
                    }
                });

                this.errors.variations = specialErrors;
            },
            deep: true
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
                let temporary_form = this.product.new_variations;

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
                //
                // do something like remove empty array inside
                // this.form.variations = result;
                this.product.new_variations = temporaryResult;
            },
            deep: true
        },
    },
    data() {
        return {
            display_currency: null,
            checkout_url: null,
            loadingModal: null,
            is_updating: false,
            is_loading: true,
            control: {
                temp_variation: {},
            },

            variation_error: null,
            variation_errors: {},

            errors: {
                product: {},
                variations: {},
                image: {},
                temp_variation: {},
            },

            product: {
                variations: [],
                new_variations: [],
                categories: [],
            },

            form_control: {
                is_manageable: false,
                is_variation_manageable: false,
                has_variation: false,
                is_pinned: false
            },

            modal_variation: null,

            usable_data: {
                variation_list: {
                    Size: 'Size',
                    Color: 'Color',
                    Material: 'Material',
                },
            },
            variations: [],
            allCategories: [],
            imageLimit: 6,
        };
    },

    mounted() {
        this.product = Product;
        console.log(Product);
        this.allCategories = Categories;
        this.display_currency = this.product.currency.toUpperCase();
        this.checkout_url = this.product.checkout_url;
        this.product.price = this.product.readable_price.toString();

        if (!this.product.has_variations) {
            this.product.new_variations = [];
        }
        _.each(this.product.variation_types, value => {
            this.control.temp_variation[value] = '';
        });

        _.each(this.product.variations, (value, index) => {
            this.product.variations[index]['price'] = value.price === null ? '' : value.price + '';
            this.product.variations[index]['quantity'] = value.quantity === null ? '' : value.quantity + '';
            this.product.variations[index]['quantity_alert_level'] = value.quantity_alert_level === null ? '' : value.quantity_alert_level + '';
        });

        this.is_loading = false;
        this.loadingModal = $('#loadingModal');
    },

    methods: {
        triggerVariation(event, status) {
            event.preventDefault();

            this.form_control.has_variation = status;

            this.product.is_manageable = false;

            if (this.form_control.has_variation) {
                this.form_control.is_manageable = false;
                this.product.quantity = '';
                this.product.quantity_alert_level = '';
                this.product.new_variations = [];
            } else {
                this.variations = [];
            }
        },
        appendListWhenBlur(event, variation) {
            if (event.target.value.length > 0) {
                // trim first

                variation.value.push(event.target.value);

                event.target.value = null;
            }
        },

        appendList(event, variation) {
            if (event.target.value.length > 0 && (event.keyCode === 13)) {
                if (this.product.new_variations.length >= 100) {
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

        removeVariantChild(event, index, list) {
            event.preventDefault();

            list.splice(index, 1);
        },

        addNewVariation(event, key) {
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

        removeVariant(event, key) {
            event.preventDefault();

            let variant = this.variations.indexOf(this.variations.find(x => x.key === key));

            this.variations.splice(variant, 1);
        },

        addVariation(event) {
            event.preventDefault();

            this.errors.temp_variation = {};

            let tempForm = {
                values: [],
            };

            _.each(this.product.variation_types, value => {
                if (!this.control.temp_variation[value]) {
                    this.errors.temp_variation[value] = 'The ' + value + ' is required.';
                } else {
                    tempForm.values.push({
                        key: value,
                        value: this.control.temp_variation[value]
                    });
                }
            });

            tempForm.is_manageable = this.product.is_manageable;


            /////
            if (this.control.temp_variation.price) {
                let indexOfPeriodForPrice = this.control.temp_variation.price.indexOf('.');
                let decimalsLengthForPrice = this.control.temp_variation.price.substr(indexOfPeriodForPrice);

                if (decimalsLengthForPrice && decimalsLengthForPrice.length > 3) {
                    this.errors.temp_variation.price = 'The price can\'t have more than two decimals.';
                } else {
                    tempForm.price = this.control.temp_variation.price;
                }
            } else {
                this.errors.temp_variation.price = 'The price field is required.';
            }

            if (this.control.temp_variation.quantity) {
                let checkForQuantity = this.control.temp_variation.quantity.match(/\./g);

                if (checkForQuantity && checkForQuantity.length > 0) {
                    this.errors.temp_variation.quantity = 'The quantity can\'t have decimals.';
                } else {
                    tempForm.quantity = this.control.temp_variation.quantity;
                }

                if (this.control.temp_variation.quantity_alert_level) {
                    let checkForQuantityAlertLevel = this.control.temp_variation.quantity_alert_level.match(/\./g);

                    if (checkForQuantityAlertLevel && checkForQuantityAlertLevel.length > 0) {
                        this.errors.temp_variation.quantity_alert_level = 'The alert level can\'t have decimals.';
                    } else {
                        tempForm.quantity_alert_level = this.control.temp_variation.quantity_alert_level;
                    }
                }
            } else if (this.control.temp_variation.quantity_alert_level) {
                this.errors.temp_variation.quantity_alert_level = 'The quantity can\'t be empty if the alert level is set.';
            } else if (tempForm.is_manageable) {
                this.errors.temp_variation.quantity = 'The quantity field is required.';
            }

            if (Object.keys(this.errors.temp_variation).length > 0) {
                return;
            }

            axios.post(this.getDomain('business/' + Business.id + '/product/' + this.product.id + '/variation', 'dashboard'), tempForm).then(data => {
                this.product.variations = data.data.variations;

                _.each(this.product.variations, (value, index) => {
                    this.product.variations[index]['price'] = value.price === null ? '' : value.price + '';
                    this.product.variations[index]['quantity'] = value.quantity === null ? '' : value.quantity + '';
                    this.product.variations[index]['quantity_alert_level'] = value.quantity_alert_level === null ? '' : value.quantity_alert_level + '';
                });

                this.errors.temp_variation = {};
                this.control.temp_variation = {};

                $('#addNewVariationModal').modal('hide');
            }).catch(error => {
                this.variation_error = error.response.data.data.id;
            });
        },

        showDeleteVariant(event, item) {
            event.preventDefault();

            this.modal_variation = item;

            $('#deleteVariationModal').modal('show');
        },

        deleteVariant(event) {
            event.preventDefault();
            if (this.modal_variation !== null) {
                axios.delete(this.getDomain('business/' + Business.id + '/product/' + this.product.id + '/variation/' + this.modal_variation.id, 'dashboard')).then(data => {
                    this.product.variations = data.data.variations;
                }).catch(error => {
                    if (error.response.status === 403) {
                        alert(error.response.data.message);
                    } else if (error.response.status === 422) {
                        this.variation_error = error.response.data.data.id;
                    } else {
                        throw error;
                    }
                }).finally(() => {
                    this.modal_variation = null;

                    $('#deleteVariationModal').modal('hide');
                });
            }
        },

        updateProduct(publish) {
            let loading = $('<span>').html('<i class="fas fa-spinner fa-spin"></i>');
            $('#publishBtn').append(' '.loading);
            $('#saveBtn').append(' '.loading);
            this.is_updating = true;

            let errors = {};

            if (!this.product.name) {
                errors.name = 'The name field is required.';
            } else if (this.product.name.length >= 255) {
                errors.name = 'The name may not be greater than 255 characters.';
            } else if (this.product.name.trim() === ''){
                errors.name = 'The name field is required.';
            }

            if (this.product.description && this.product.description.length > 65536) {
                errors.description = 'The description may not be greater than 65,536 characters.';
            } else if (this.product.price < 1) {
                errors.product.price = 'The price can\'t be lower than $1.';
            } else if (this.product.price > 99999999999999) {
                errors.product.price = 'The price can\'t be greater than $99999999999999.';
            }

            if (this.product.stock_keeping_unit != null && this.product.stock_keeping_unit.length >= 255) {
                errors.stock_keeping_unit = 'The SKU may not be greater than 255 characters.';
            }

            if (this.product.variations_count <= 1 && this.product.is_manageable) {
                if (!this.product.quantity) {
                    errors.product.quantity = 'The quantity field is required when the product inventory is tracked.';
                } else if (isNaN(this.product.quantity)) {
                    errors.product.quantity = 'The quantity must be a number.';
                }

                if (this.product.quantity_alert_level && this.product.quantity_alert_level.length && isNaN(this.product.quantity_alert_level)) {
                    errors.product.quantity_alert_level = 'The quantity alert must be a number.';
                }
            }

            this.errors.product = errors;

            let specialErrors = {};
            let manageable = this.form_control.is_variation_manageable;
            _.forEach(this.product.new_variations, function (value) {
                let errorForThis = [];

                if (value.price !== '') {
                    let indexOfPeriodForPrice = value.price.indexOf('.');
                    let decimalsLengthForPrice = value.price.substr(indexOfPeriodForPrice);

                    if (isNaN(value.price)) {
                        errorForThis.push('The price must be a number.');
                    } else if (decimalsLengthForPrice && decimalsLengthForPrice.length > 3) {
                        errorForThis.push('The price can\'t have more than two decimals.');
                    }
                }

                if (value.quantity !== '') {
                    let checkForQuantity = value.quantity.match(/\./g);

                    if (isNaN(value.quantity)) {
                        errorForThis.push('The quantity must be a number.');
                    } else if (checkForQuantity && checkForQuantity.length > 0) {
                        errorForThis.push('The quantity can\'t have decimals.');
                    }

                    if (value.quantity_alert_level) {
                        let checkForQuantityAlertLevel = value.quantity_alert_level.match(/\./g);

                        if (isNaN(value.quantity_alert_level)) {
                            errorForThis.push('The alert level must be a number.');
                        } else if (checkForQuantityAlertLevel && checkForQuantityAlertLevel.length > 0) {
                            errorForThis.push('The alert level can\'t have decimals.');
                        }
                    }
                } else if (value.quantity_alert_level) {
                    value.quantity_alert_level = null;
                }

                if (errorForThis.length > 0) {
                    specialErrors[value.id] = errorForThis;
                }
            });

            this.variation_errors = specialErrors;

            if (Object.keys(this.errors.product).length > 0 || Object.keys(this.errors.variations).length > 0 || Object.keys(this.variation_errors).length > 0) {
                loading.remove();
                this.is_updating = false;
                return;
            }

            let form = new FormData();

            form.append('name', this.product.name);
            form.append('price', this.product.price);
            if (this.product.stock_keeping_unit != null)
                form.append('stock_keeping_unit', this.product.stock_keeping_unit);
            if (this.product.description != null) {
                form.append('description', this.product.description);
            }
            form.append('is_manageable', this.product.is_manageable === true ? 1 : 0);
            form.append('publish', (publish === true ? 1 : 0));
            form.append('is_pinned', this.product.is_pinned == true ? 1 : 0);

            if (this.product.categories != null && this.product.categories.length > 0) {
                var product_categories = [];
                _.each(this.product.categories, function (category, key) {
                    product_categories.push(category.id);
                });
                form.append('business_product_category_id', JSON.stringify(product_categories));
            }

            if (this.product.is_manageable && this.product.variations_count <= 1 && this.product.new_variations.length < 1) {
                form.append('quantity', this.product.quantity);
                if (this.product.quantity_alert_level != null) form.append('quantity_alert_level', this.product.quantity_alert_level);
            } else if (this.product.variations_count > 1 && this.product.variations.length > 0) {
                form.append('quantity', (this.product.is_manageable ? 1 : 0));

                let i = 0;

                _.forEach(this.product.variations, value => {
                    let temp = {
                        id: value.id,
                        price: value.price,
                    };
                    form.append('variation[' + i + '][id]', value.id);
                    form.append('variation[' + i + '][price]', value.price);

                    if (this.product.is_manageable) {
                        form.append('variation[' + i + '][quantity]', parseInt(value.quantity));
                        form.append('variation[' + i + '][quantity_alert_level]', parseInt(value.quantity_alert_level));
                    }

                    i++;

                });
            } else if (!this.form_control.is_manageable && this.form_control.has_variation && this.product.new_variations.length > 0) {
                form.append('quantity', this.form_control.is_variation_manageable ? 1 : 0);
                form.append('is_manageable', this.form_control.is_variation_manageable ? 1 : 0);

                let i = 0;

                _.forEach(this.product.new_variations, (value) => {
                    let j = 0;

                    _.forEach(value.values, function (v) {
                        form.append('new_variation[' + i + '][values][' + j + '][key]', v.key);
                        form.append('new_variation[' + i + '][values][' + j + '][value]', v.value);

                        j++;
                    });

                    if (this.form_control.is_variation_manageable) {
                        form.append('new_variation[' + i + '][quantity]', parseInt(value.quantity));
                        form.append('new_variation[' + i + '][quantity_alert_level]', parseInt(value.quantity_alert_level));
                    }

                    form.append('new_variation[' + i + '][price]', value.price);

                    i++;
                });
            }
            this.loadingModal.modal('show');
            form.append("_method", "put");

            axios.post(this.getDomain('business/' + Business.id + '/product/' + this.product.id, 'dashboard'), form, {
                headers: {
                    'Accept': 'application/json',
                }
            }).then(({data}) => {
                window.location.href = data.redirect;
            }).catch(({response}) => {
                //
            }).finally(() => {
                loading.remove();
                this.loadingModal.modal('hide');
                this.is_updating = false;
            });
        },

        deleteProduct() {
            $('#confirmDeleteModal').modal('show');
        },

        confirmDeleteProduct() {
            this.is_updating = true;

            axios.delete(this.getDomain('business/' + Business.id + '/product/' + this.product.id, 'dashboard')).then(({data}) => {
                window.location.href = data.redirect_url;
            });
        },

        duplicateProduct() {
            axios.post(this.getDomain('business/' + Business.id + '/product/duplicate/' + this.product.id, 'dashboard')).then(({data}) => {
                window.location.href = data.redirect;
            });
        },

        validateValue(currencyRule, amount) {
            if (amount !== '') {
                let decimalLength = 0;
                let split = amount.split(".");

                if (split.length > 1) {
                    decimalLength = split[1].length || 0;
                }

                if (decimalLength > 2) {
                    return 'The selling price can\'t have more than 2 decimals.';
                } else if (currencyRule.is_zero_decimal && amount % 1 !== 0) {
                    return 'The selling price must be a whole number for this currency.';
                }
            }

            return null;
        },

        disallowDecimal(event) {
            if (event.keyCode === 190) {
                event.preventDefault();
            }
        },

        deleteImage(id, key) {
            axios.delete(this.getDomain('business/' + Business.id + '/product/' + this.product.id + '/image/' + id, 'dashboard')).then(({data}) => {
                (this.product.image).splice(key, 1);
            }).catch(({error}) => {
                console.log(error);
            });
        },

        handleImage(event) {
            event.preventDefault();

            event.target.files.forEach((file) => {
                const max = 1600;
                const reader = new FileReader();

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

                        let form = new FormData();

                        form.append('image', this.dataURItoBlob(ctx.canvas.toDataURL("image/jpeg", 0.7)), 'avatar.jpg');

                        axios.post(this.getDomain('business/' + Business.id + '/product/' + this.product.id + '/image', 'dashboard'), form).then(({data}) => {
                            this.product.image = data.image;
                        });
                    };
                };

                reader.onerror = error => console.log(error);

            });
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

        copy() {
            this.$clipboard(this.checkout_url);

            alert('Copied to clipboard');
        },

        changeProductPrice() {
            _.forEach(this.product.variations, (value) => {
                value.price = this.product.price;
            });
        }
    },
}
</script>
