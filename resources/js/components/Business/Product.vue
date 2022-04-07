<style src="vue-multiselect/dist/vue-multiselect.min.css"></style>
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
            <div class="card-body">
                <h2 class="text-primary mb-0 title">Add Product</h2>
            </div>
            <div class="card-body border-top">
                <div class="form-group">
                    <label for="name">Product Name <span class="text-danger">*</span></label>
                    <input id="name" type="text" v-model="form.name" :class="{
                        'is-invalid' : errors.name,
                    }" class="form-control bg-light" title="Product Name" placeholder="T-Shirt">
                    <span v-if="errors.name" class="invalid-feedback" role="alert">{{ errors.name }}</span>
                </div>
                <div class="form-group">
                    <label>SKU</label>
                    <input type="text" v-model="form.stock_keeping_unit" :class="{ 'is-invalid' : errors.stock_keeping_unit }"
                           class="form-control bg-light" title="SKU" placeholder="SKU">
                    <span v-if="errors.stock_keeping_unit" class="invalid-feedback d-block" role="alert">{{
                            errors.stock_keeping_unit
                        }}</span>
                </div>
                <div class="form-group">
                    <label for="price">Selling Price <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon3">{{ display_currency }}</span>
                        </div>
                        <input id="price" v-model="form.price" :class="{
                            'is-invalid' : errors.price
                        }" class="form-control bg-light" placeholder="10.00" step="0.01" title="Selling Price" @keyup="changeProductPrice">
                    </div>
                    <span v-if="errors.price" class="d-block small text-danger w-100 mt-1" role="alert">
                        {{ errors.price }}
                    </span>
                </div>
                <div class="form-group mb-0">
                    <label for="description">Description</label>
                    <textarea id="description" v-model="form.description" :class="{ 'is-invalid' : errors.description }"
                              class="form-control bg-light" rows="4" title="Description"
                              placeholder="A T-shirt is a style of unisex fabric shirt named after the T shape of its body and sleeves. Traditionally it has short sleeves and a round neckline, known as a crew neck, which lacks a collar…"></textarea>
                    <span v-if="errors.description" class="invalid-feedback" role="alert">{{
                            errors.description
                        }}</span>
                </div>
            </div>
            <div v-if="!form_control.has_variation" class="card-body border-top">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="isManageable"
                           v-model="form_control.is_manageable">
                    <label class="custom-control-label" for="isManageable">Manage Inventory</label>
                </div>
                <div v-if="form_control.is_manageable" class="mt-3">
                    <div class="form-row">
                        <div class="form-group col-12 col-sm-6 mb-sm-0">
                            <label for="quantity">Available Quantity <span class="text-danger">*</span></label>
                            <input id="quantity" v-model="form.quantity" :class="{ 'is-invalid' : errors.quantity }"
                                   class="form-control bg-light" title="Available Quantity" placeholder="10"
                                   @keydown="disallowDecimal($event)">
                            <span v-if="errors.quantity" class="invalid-feedback" role="alert">{{
                                    errors.quantity
                                }}</span>
                        </div>
                        <div class="form-group col-12 col-sm-6 mb-0">
                            <label for="quantity_alert_level">Low Quantity Alert</label>
                            <input id="quantity_alert_level" v-model="form.quantity_alert_level" :class="[
                                    {'is-invalid' : errors.quantity_alert_level},
                                    {
                                        'bg-light': form.quantity !== null && form.quantity !== '',
                                    }
                                ]" class="form-control" title="Low Quantity Alert" placeholder="3"
                                   :disabled="form.quantity === null || form.quantity === ''"
                                   @keydown="disallowDecimal($event)">
                            <span v-if="errors.quantity_alert_level" class="invalid-feedback"
                                  role="alert">{{ errors.quantity_alert_level }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body border-top">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="isPinned"
                           v-model="form_control.is_pinned">
                    <label class="custom-control-label" for="isPinned">Featured Products</label>
                </div>
            </div>
            <div class="card-body border-top">
                <h5 class="font-weight-bold mb-3">Product Categories</h5>
                <multiselect v-model="form.categories" :options="allCategories" :multiple="true" :close-on-select="true"
                             :clear-on-select="false" placeholder="Choose categories" label="name"
                             track-by="name" :max="5"></multiselect>
            </div>
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
                       @click="addVariation($event, index)" class="btn btn-outline-secondary btn-sm mr-1" href="#">
                        + {{ item }}
                    </a>
                </div>
                <div v-if="form.variations.length > 0" class="custom-control custom-checkbox mt-3">
                    <input type="checkbox" class="custom-control-input" id="isVariationManageable"
                           v-model="form_control.is_variation_manageable">
                    <label class="custom-control-label" for="isVariationManageable">Manage Variants Inventory</label>
                </div>
            </div>
            <table v-if="form_control.has_variation && form.variations.length > 0"
                   class="table table-hover border-top mb-0">
                <tr class="bg-light">
                    <th scope="col">Variants</th>
                    <th scope="col">Selling Price</th>
                    <th scope="col" v-if="form_control.is_variation_manageable">Quantity</th>
                    <th scope="col" v-if="form_control.is_variation_manageable">Low Quantity Alert</th>
                </tr>
                <template v-for="(item, key) in form.variations">
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
                                       class="form-control" :placeholder="form.price" step="0.01">
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
            <div class="card-body border-top">
                <h5 class="font-weight-bold mb-3">Images</h5>
                <small class="d-block">The optimal product image size is 600*600, 800*800 and 1000*1000.</small>
                <small>You can upload up to 6 images.</small>
                <div v-if="(images).length < imageLimit">
                    <label class="d-inline-flex mb-1" for="productImage">
                        <input type="file" id="productImage" class="custom-file-input d-none" accept="image/*"
                               @change="addImage($event)" multiple="multiple">
                        <span id="uploadBtn" class="btn btn-primary">
                        <i class="fas fa-folder-open"></i> Choose image
                    </span>
                    </label>
                </div>
                <template v-if="(images).length > 0">
                    <div class="row mt-4">
                        <template v-for="(image,key) in images">
                            <div class="col-md-4">
                        <span>
                            <a class="text-danger" href="#" @click="removeImage($event, key)">Remove Image</a>
                        </span>
                                <img :src="image" :title="form.name" class="img-fluid rounded"
                                     style="max-width: 200px">
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>
        <button id="createBtn" class="btn btn-success btn-lg btn-block mb-3 shadow-sm" @click="createProduct(true)"
                :disabled="is_updating">
            Publish
            <i class="fas fa-spin fa-spinner" :class="{
                'd-none' : !is_updating
            }"></i></button>
        <button id="saveBtn" class="btn btn-secondary btn-sm btn-block shadow-sm" @click="createProduct(false)"
                :disabled="is_updating">
            Save as Draft
            <i class="fas fa-spin fa-spinner" :class="{
                'd-none' : !is_updating
            }"></i>
        </button>
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
        form: {
            handler(values) {
                if (this.is_loading) {
                    return;
                }

                if (values.price !== '') {
                    let indexOfPeriodForPrice = values.price.indexOf('.');
                    let decimalsLengthForPrice = values.price.substr(indexOfPeriodForPrice);

                    if (decimalsLengthForPrice && decimalsLengthForPrice.length > 3) {
                        this.errors.price = 'The price can\'t have more than two decimals.';
                    } else if (values.price < 1) {
                        this.errors.price = 'The price can\'t be lower than $1.';
                    } else {
                        this.errors.price = null;
                    }
                }

                if (values.quantity !== '') {
                    let checkForQuantity = values.quantity.match(/\./g);

                    if (checkForQuantity && checkForQuantity.length > 0) {
                        this.errors.quantity = 'The quantity can\'t have decimals.';
                    } else {
                        this.errors.quantity = null;
                    }

                    if (values.quantity_alert_level) {
                        let checkForQuantityAlertLevel = values.quantity_alert_level.match(/\./g);

                        if (checkForQuantityAlertLevel && checkForQuantityAlertLevel.length > 0) {
                            this.errors.quantity_alert_level = 'The alert level can\'t have decimals.';
                        } else {
                            this.errors.quantity_alert_level = null;
                        }
                    }
                } else if (values.quantity_alert_level) {
                    this.errors.quantity_alert_level = 'The quantity can\'t be empty if the alert level is set.';
                }

                let specialErrors = {};
                let manageable = this.form_control.is_variation_manageable;

                _.forEach(values.variations, function (value) {
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
                let temporary_form = this.form.variations;

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
                                price: this.form.price,
                                quantity: '0',
                                quantity_alert_level: '',
                            }));
                        } else {
                            if (current_one.price === '') {
                                current_one.price = this.form.price;
                            }

                            temporaryResult.push(_.assign({}, value, current_one));
                        }
                    }
                });
                //
                // do something like remove empty array inside
                // this.form.variations = result;
                this.form.variations = temporaryResult;
            },
            deep: true
        },
    },

    data() {
        return {
            is_loading: true,
            is_updating: false,
            display_currency: null,
            errors: {},
            loadingModal: null,

            usable_data: {
                variation_list: {
                    Size: 'Size',
                    Color: 'Color',
                    Material: 'Material',
                },
            },

            form: {
                name: '',
                price: '',
                stock_keeping_unit: '',
                description: '',
                quantity: '',
                quantity_alert_level: '',
                variations: [],
                categories: [],
            },

            form_control: {
                is_manageable: false,
                is_variation_manageable: false,
                has_variation: false,
                is_pinned: false,
            },

            variations: [],
            variation_errors: {},
            allCategories: [],
            images: [],
            imageLimit: 6,
        };
    },

    mounted() {
        this.display_currency = Business.currency.toUpperCase();
        this.allCategories = Categories;
        this.is_loading = false;
        this.loadingModal = $('#loadingModal');
    },

    methods: {
        triggerVariation(event, status) {
            event.preventDefault();

            this.form_control.has_variation = status;

            if (this.form_control.has_variation) {
                this.form_control.is_manageable = false;
                this.form.quantity = '';
                this.form.quantity_alert_level = '';
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
                if (this.form.variations.length >= 100) {
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

        removeVariant(event, key) {
            event.preventDefault();

            let variant = this.variations.indexOf(this.variations.find(x => x.key === key));

            this.variations.splice(variant, 1);
        },

        createProduct(publish) {
            let loading = $('<span>').html('<i class="fas fa-spinner fa-spin"></i>');

            $('#createBtn').append(' '.loading);
            $('#saveBtn').append(' '.loading);

            this.is_updating = true;

            let errors = {};

            if (!this.form.name) {
                errors.name = 'The name field is required.';
            } else if (this.form.name.length >= 255) {
                errors.name = 'The name may not be greater than 255 characters.';
            }

            if (this.form.stock_keeping_unit.length >= 255) {
                errors.stock_keeping_unit = 'The SKU may not be greater than 255 characters.';
            }

            if (!this.form.price) {
                errors.price = 'The selling price field is required.';
            } else if (isNaN(this.form.price)) {
                errors.price = 'The selling price must be a number.';
            } else if (this.form.price < 1) {
                errors.price = 'The price can\'t be lower than $1.';
            } else if (this.form.price > 99999999999999) {
                errors.price = 'The price can\'t be greater than $99999999999999.';
            }

            if (this.form.description && this.form.description.length > 65536) {
                errors.description = 'The description may not be greater than 65,536 characters.';
            }

            this.showError(_.first(Object.keys(this.errors)));

            if (!this.form_control.has_variation && this.form_control.is_manageable) {
                if (!this.form.quantity) {
                    errors.quantity = 'The quantity field is required when the product inventory is tracked.';
                } else if (isNaN(this.form.quantity)) {
                    errors.quantity = 'The quantity must be a number.';
                }

                if (this.form.quantity_alert_level.length && isNaN(this.form.quantity_alert_level)) {
                    errors.quantity_alert_level = 'The quantity alert must be a number.';
                }
            }

            this.errors = errors;

            if (Object.keys(this.errors).length > 0
                || (this.has_variation && this.form.variations.length === 0)
                || Object.keys(this.variation_errors).length > 0) {
                loading.remove();

                this.is_updating = false;

                return;
            }

            let form = new FormData();

            form.append('name', this.form.name);
            form.append('price', this.form.price);
            form.append('stock_keeping_unit', this.form.stock_keeping_unit);
            form.append('description', this.form.description);

            var product_categories = [];
            _.each(this.form.categories, function (category, key) {
                product_categories.push(category.id);
            });

            form.append('business_product_category_id', JSON.stringify(product_categories));
            form.append('is_pinned', this.form_control.is_pinned ? 1 : 0);

            if (!this.form_control.is_manageable && this.form_control.has_variation && this.form.variations.length > 0) {
                form.append('is_manageable', this.form_control.is_variation_manageable ? 1 : 0);

                let i = 0;

                _.forEach(this.form.variations, (value) => {
                    let j = 0;

                    _.forEach(value.values, function (v) {
                        form.append('variation[' + i + '][values][' + j + '][key]', v.key);
                        form.append('variation[' + i + '][values][' + j + '][value]', v.value);

                        j++;
                    });

                    if (this.form_control.is_variation_manageable) {
                        form.append('variation[' + i + '][quantity]', parseInt(value.quantity));
                        form.append('variation[' + i + '][quantity_alert_level]', parseInt(value.quantity_alert_level));
                    }

                    form.append('variation[' + i + '][price]', value.price);

                    i++;
                });
            } else {
                form.append('is_manageable', this.form_control.is_manageable ? 1 : 0);

                if (this.form_control.is_manageable && !this.has_variation) {
                    form.append('quantity', parseInt(this.form.quantity));
                    form.append('quantity_alert_level', parseInt(this.form.quantity_alert_level));
                }
            }

            if ((this.images).length > 0) {
                let i = 1;
                var self = this;
                _.forEach(this.images, function (image) {
                    form.append('image' + i, self.dataURItoBlob(image), i + '.jpg');
                    i++;
                });
            }

            form.append('publish', publish === true ? 1 : 0);

            this.loadingModal.modal('show');

            console.log(form);
            axios.post(this.getDomain('business/' + Business.id + '/product', 'dashboard'), form, {
                headers: {
                    'Accept': 'application/json',
                }
            }).then(({data}) => {
                window.location.href = data.redirect_url;
            }).catch(({response}) => {
                if (response.status === 422) {
                    _.forEach(response.data.errors, (value, key) => {
                        this.errors[key] = _.first(value);
                    });

                    this.showError(_.first(Object.keys(this.errors)));
                }
            }).finally(() => {
                loading.remove();
                this.loadingModal.modal('hide');

                this.is_updating = false;
            });
        },

        validateValue(currencyRule, amount) {
            if (amount !== null && amount !== '') {
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

        addImage(event) {
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
                            console.log('----');
                            const scaleFactor = max / img.width;

                            elem.width = max;
                            elem.height = img.height * scaleFactor;

                            width = max;
                            height = img.height * scaleFactor;
                        } else {
                            console.log('|||||');
                            const scaleFactor = max / img.height;

                            elem.width = img.width * scaleFactor;
                            elem.height = max;

                            width = img.width * scaleFactor;
                            height = max;
                        }

                        ctx = elem.getContext('2d');
                        ctx.drawImage(img, 0, 0, width, height);

                        this.images.push(ctx.canvas.toDataURL("image/jpeg", 0.7));
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

        removeImage(event, key) {
            event.preventDefault();

            (this.images).splice(key, 1);
        },

        showError(firstErrorKey) {
            if (firstErrorKey !== undefined) {
                this.scrollTo('#' + firstErrorKey);

                $('#' + firstErrorKey).focus();
            }

            this.is_updating = false;
        },

        changeProductPrice() {
            _.forEach(this.form.variations, (value) => {
                value.price = this.form.price;
            });
        }
    },
}
</script>
