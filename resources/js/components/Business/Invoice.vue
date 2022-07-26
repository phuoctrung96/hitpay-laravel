<style lang="scss">
/* Customize the radio checkbox */
.label-checkbox {
    position: relative;
    padding-left: 32px;
    margin-bottom: 22px;
    cursor: pointer;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
}

.label-checkbox input {
    position: absolute;
    opacity: 0;
    cursor: pointer;
    height: 0;
    width: 0;
}

.checkmark {
    position: absolute;
    top: 50%;
    left: 0;
    height: 24px;
    width: 24px;
    border: 1px solid #D4D6DD;
    border-radius: 50%;
    margin-top: -12px;
}

.label-checkbox .checkmark:after {
    top: 5px;
    left: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.label-checkbox input:checked ~ .checkmark {
    background-color: #FFF;
}

.label-checkbox .checkmark:after {
    background: #011B5F;
}

.checkmark:after {
    content: "";
    position: absolute;
    display: none;
}

.label-checkbox input:checked ~ .checkmark:after {
    display: block;
}

</style>
<template>

    <div class="invoice-section">
        <div class="card-body border-top">
            <div class="logo">
                <img :src="business_logo" alt="logo" />
            </div>
            <div class="d-flex d-2col justify-content-between">
                <div class="form-group w-50 mr-4">
                    <label for="invoice_date" class="d-block">Invoice date</label>
                    <datepicker id="invoice_date" v-model="invoice.invoice_date" :bootstrap-styling="true" :highlighted="highlighted"
                                input-class="bg-white is-dropdown" placeholder="Select date" :format="'dd/MM/yyyy'" class="w-100"
                                :class="{
                    'border border-danger rounded' : errors.invoice_date
                }"></datepicker>
                    <span v-if="errors.invoice_date" class="text-danger small" role="alert">{{
                            errors.invoice_date
                        }}</span>
                </div>
                <div class="form-group w-50">
                    <label for="due_date" class="d-block">Due date</label>
                    <datepicker id="due_date" v-model="invoice.due_date" :bootstrap-styling="true" :highlighted="highlighted"
                                input-class="bg-white is-dropdown" placeholder="Select date" :format="'dd/MM/yyyy'" class="w-100"
                                :disabled-dates="disableDates"
                                :class="{
                    'border border-danger rounded' : errors.due_date
                }"></datepicker>
                    <a v-if="invoice.due_date" href="#" class="small text-danger" @click.prevent="removeDueDate">Remove</a>
                    <span v-if="errors.due_date" class="text-danger small" role="alert">{{ errors.due_date }}</span>
                </div>
            </div>
            <div v-if="!invoice.customer_id">
                <label for="customer_id">Select customer</label>
                <input id="customer_id" class="form-control" title="" v-model="search_customer" :class="{
                    'is-invalid' : errors.customer_id
                }" placeholder="Enter customer email to search" @keyup="searchCustomer">
                <div :style="outerDropDown">
                    <div :style="innerDropDown" class="shadow-sm search-results">
                        <div v-if="customers_result.length > 0" class="bg-white is-dropdown">
                            <div v-for="(customer, index) in customers_result" class="item p-3 d-lg-flex justify-content-between align-items-center"
                                 :class="{
                        'border-top': index !== 0,
                    }">
                                <span class="small email font-weight-bold">{{ customer.email }}</span>
                                <template v-if="customer.name">
                                    <span class="small text-muted">{{ customer.name }}</span>
                                </template>
                                <span v-if="customer.address" class="small text-muted">{{
                                        customer.address.slice(0, 15)
                                    }}...</span>
                                <button class="btn btn-sm px-4" :style="{backgroundColor: mainColor, color: 'white'}"
                                        @click="addCustomer(customer)"><img src="/icons/ico-plus-white.svg" class="icon-plus"> Add
                                </button>
                            </div>
                        </div>
                        <div v-if="search_customer.length > 0" class="bg-white border-top p-3">
                            <button class="btn"
                                    data-toggle="modal"
                                    data-target="#createCustomerModal"
                                    :style="{backgroundColor: mainColor, color: 'white', fontSize: '12px'}"
                                    :disabled="is_processing">
                                <img src="/icons/ico-plus-white.svg" class="icon-plus"> Add new customer
                            </button>
                        </div>
                    </div>
                </div>
                <span class="invalid-feedback" role="alert">
                        {{ errors.customer_id }}
                    </span>
            </div>
            <div v-else>
                <label>Select customer</label>
                <input class="form-control" title="" v-model="customer.email" disabled>
                <a href="#" class="small text-danger" @click.prevent="removeCustomer">Remove</a>
            </div>
            <div class="d-flex d-2col justify-content-between mt-3">
                <div class="form-group w-50 mr-4">
                    <label>Invoice number </label>
                    <div class="input-group">
                        <!-- <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon3">INV-</span>
                        </div> -->
                        <input v-model="invoice.invoice_number"
                               :class="{ 'is-invalid' : errors.invoice_number }"
                               class="form-control" title="Amount"
                               :disabled="is_processing || invoice.auto_invoice_number" maxlength="200">
                    </div>
                    <div class="invoice-number-checkox">
                        <input type="checkbox" id="auto_invoice_number" class="custom-checkbox" title=""
                                @change="changeAutoInvoice"
                               v-model="invoice.auto_invoice_number"
                               :class="{
                    'vertical-align-middle': true,
                    'is-invalid' : errors.auto_invoice_number
                }" :disabled="is_processing ">
                        <label for="auto_invoice_number" class="">Generate invoice number</label>
                        <span v-if="errors.invoice_number" class="d-block small text-danger w-100 mt-1" role="alert">
                    {{ errors.invoice_number }}</span>
                    </div>
                </div>
                <div class="form-group w-50">
                    <label for="currency_list">Select Currency </label>
                    <select id="currency_list" class="custom-select" v-model="invoice.currency"
                            :class="{
                    'is-invalid' : errors.currency && invoice.currency == ''
                }" :disabled="is_processing">
                        <option v-for="(value, key) in currency_list" :value="value"
                                :selected="value === invoice.currency">
                            {{ key }}
                        </option>
                    </select>
                    <span v-if="typeof errors.currency == 'string'" class="invalid-feedback" role="alert">
                    {{ errors.currency }}
                </span>
                </div>
            </div>
            <div class="payment-option">
                <label class="label-checkbox mr-3 mt-2">Payment by products
                    <input type="radio" :value="true" v-model="payment_by_products" :disabled="is_processing"
                           @change="total_amount=0" @click="added_products=[{}]">
                    <span class="checkmark"></span>
                </label>
                <label class="label-checkbox">Payment by fixed amount
                    <input type="radio" :value="false" v-model="payment_by_products"
                           :disabled="is_processing" @change="total_amount=0; added_products=[{}]">
                    <span class="checkmark"></span>
                </label>
            </div>
            <div class="add-product-section bg-light">
                <div class="top-section">
                    <template v-if="payment_by_products">
                        <div class="table-items">
                            <div class="lg-title">
                                <div class="field search">Item</div>
                                <div class="field qty">Qty</div>
                                <div class="field price">Price</div>
                                <div class="field discount">Discount</div>
                                <div class="field total">Total</div>
                                <!-- <div class="field delete"></div> -->
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
                                                                        <span class="text-muted">{{
                                                                                currency_display
                                                                            }}{{
                                                                                Number(variation.price).toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,')
                                                                            }}</span>
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
                                                                <span class="text-muted">{{
                                                                        currency_display
                                                                    }}{{
                                                                        Number(product.variations[0].price).toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,')
                                                                    }}</span>
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
                                                            <p class="text-muted font-italic mb-0">No product found matches
                                                                the
                                                                keywords.</p>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                        <template
                                            v-if="!added_products[key].product && is_searching_product && search_products_key[key]">
                                            <div class="card-body p-4">
                                                <p class="text-muted font-italic mb-0"><i
                                                    class="fas fa-spin fa-spinner"></i>
                                                </p>
                                            </div>
                                        </template>
                                    </div>
                                    <div class="field qty">
                                        <label class="title">Qty</label>
                                        <input v-model.number="added_products[key].quantity" title="Quantity"
                                               class="form-control" @keypress="isNumber($event);">
                                    </div>
                                    <div class="field price">
                                        <label class="title">Price</label>
                                        <input type="text" class="form-control" disabled
                                               :value="added_products[key].variation ? added_products[key].variation.price.toFixed(2) : ''">
                                    </div>
                                    <div class="field discount">
                                        <label class="title">Discount</label>
                                        <input v-model.number="added_products[key].discount" title="Discount"
                                               class="form-control"
                                               @change="checkDecimal" @keypress="isNumber($event);" @keyup="checkDiscount(added_products[key])">
                                    </div>
                                    <div class="field total align-items-center justify-content-between">
                                        <label class="title mb-0">Total</label>
                                        <template v-if="added_products[key].product">
                                            {{ itemPriceWithDiscount(added_products[key]).toFixed(2) }}
                                        </template>
                                    </div>
                                    <div class="field delete">
                                        <a class="btn-delete" v-if="key!=0" href="#" @click="removeProduct($event, key)"><img src="/images/delete_icon.svg" alt="delete"></a>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <span v-if="errors.product_quantity" class="invalid-feedback d-block mb-4"
                              role="alert">{{ errors.product_quantity }}</span>
                        <span v-if="errors.product_discount" class="invalid-feedback d-block mb-4"
                              role="alert">{{ errors.product_discount }}</span>
                        <span v-if="errors.product_exist" class="invalid-feedback d-block mb-4"
                              role="alert">{{ errors.product_exist }}</span>
                        <button class="btn btn-plus"
                                :style="{border : '1px solid '+mainColor, color: mainColor, backgroundColor: 'white', fontSize: '14px'}"
                                @click="added_products.push({})" v-if="added_products.length<30"> <span>Add Item</span>
                        </button>
                    </template>
                    <template v-else>
                        <input v-model.number="total_amount" @change="checkDecimal" @keypress="isNumber($event);"
                               :class="{ 'is-invalid' : errors.amount }"
                               class="form-control" step="0.01" title="Amount">
                        <span class="invalid-feedback" role="alert">
                            {{ errors.amount }}
                        </span>
                    </template>
                    <a v-if="!enable_tax_setting" href="#" @click.prevent="enable_tax_setting = true" class="d-block mt-3">Add tax</a>
                    <div v-else class="tax-setting mt-3">
                        <div class="is-tax-setting d-flex justify-content-between align-items-center">
                            <div class="dropdown mr-3">
                                <select id="tax_setting" @change="checkDecimal();"
                                        v-model="invoice.tax_setting"
                                        class="form-control custom-select"
                                        :class="{
                                        'is-invalid' : errors.tax_setting
                                    }"
                                        :disabled="is_processing"
                                        style="font-size: 14px"
                                >
                                    <option value="" disabled>Select Tax Setting</option>
                                    <option v-for="tax in tax_list" :value="tax.id">
                                        {{ tax.name }}
                                    </option>
                                </select>
                                <span class="invalid-feedback" role="alert">
                                    {{ errors.tax_setting }}
                                </span>
                                <button class="btn mt-1"
                                    data-toggle="modal"
                                    data-target="#createTaxModal"
                                    :style="{backgroundColor: mainColor, color: 'white', fontSize: '12px'}"
                                    :disabled="is_processing">
                                <img src="/icons/ico-plus-white.svg" class="icon-plus"> Add New Tax
                                </button>
                            </div>
                            <span>Tax amount : {{ taxAmount() }}</span>
                        </div>
                        <div class="field delete">
                            <a class="btn-delete" v-if="key!=0" href="#" @click="removeTax($event)"><img src="/images/delete_icon.svg" alt="delete"></a>
                        </div>
                    </div>
                </div>
                <div class="total border-top">
                    <div class="is-total d-flex justify-content-between">
                        <span>Total</span>
                        <span class="amount">{{invoice.currency.toUpperCase()}} {{ invoiceAmount() }}</span>
                    </div>
                    <p class="invalid-feedback d-block" role="alert" v-if="errors.amount">{{ errors.amount }}
                    </p>
                </div>
            </div>
            <div class="mt-3">
                <input type="checkbox" id="allow_partial_payments" class="custom-checkbox" title=""
                       v-model="invoice.allow_partial_payments"
                       :disabled="is_processing ">
                <label for="allow_partial_payments" class="">Allow partial payments</label>
            </div>
            <template v-if="invoice.allow_partial_payments">
                <div class="add-payment-method">
                    <div v-for="(payment, index) in partial_payments" class="item mt-2">
                    Payment {{ (index + 1) }}
                        <div class="d-flex justify-content-between payment-method mt-3">
                            <div class="form-group w-50 mr-4">
                                <input type="text" class="form-control" v-model="partial_payments[index].amount"
                                       placeholder="Amount" @change="checkDecimal" @keypress="isNumber($event);">
                            </div>
                            <div class="form-group w-50">
                                <datepicker :id="`due_date_${index}`" v-model="partial_payments[index].due_date"
                                            :bootstrap-styling="true"
                                            :disabled-dates="disableDatesForPartialPayments"
                                            input-class="bg-white" placeholder="Due date" :format="'dd/MM/yyyy'"
                                            class="w-100"
                                ></datepicker>
                            </div>
                            <a v-if="index!=0" href="#" class="btn-delete p-2" @click="removePayment($event, index)">
                                <img src="/images/delete_icon.svg" alt="delete">
                            </a>
                        </div>
                    </div>
                    <span v-if="errors.partial_amount" class="text-danger small d-block mb-3" role="alert">{{
                            errors.partial_amount
                        }}</span>
                    <button class="btn btn-plus"
                            :style="{border : '1px solid '+mainColor, color: mainColor, backgroundColor: 'white', fontSize: '14px'}"
                            @click="partial_payments.push({})"><span>Add Payment</span>
                    </button>
                </div>
            </template>
            <div class="form-group mt-3">
                <label for="reference">Reference</label>
                <input id="reference" class="form-control" title="" v-model="invoice.reference" :class="{
                    'is-invalid' : errors.reference
                }" placeholder="November supply invoice" :disabled="is_processing">
                <span class="invalid-feedback" role="alert">{{ errors.reference }}</span>
            </div>
            <div class="form-group mt-3">
                <label for="description">Description</label>
                <textarea id="description" v-model="invoice.memo" class="form-control description" rows="3" :class="{
                    'is-invalid' : errors.memo
                }" placeholder="Details of this invoice…" :disabled="is_processing"></textarea>
                <span class="invalid-feedback" role="alert">{{ errors.memo }}</span>
            </div>
            <div class="form-group mt-3">
                <template v-if="invoice.file_path">
                    <span>{{ invoice.file_path }}</span>
                    <a href="#" @click="removeFile($event)" class="btn-delete">
                        <img src="/images/delete_icon.svg" alt="delete">
                    </a>
                </template>
                <template v-else-if="!attached_file">
                    <label for="attached_file" class="attached-file"><img src="/images/ico-attached-file.svg" alt="attached file"> Attach file</label>
                    <input type="file" class="form-control-file" id="attached_file" @change="uploadFile"
                           :class="{'is-invalid' : errors.attached_file}"/>
                </template>
                <template v-else>
                    <span>{{ attached_file.name }}</span>
                    <a href="#" @click="removeFile($event)" class="btn-delete">
                        <img src="/images/delete_icon.svg" alt="delete">
                    </a>
                </template>
                <span class="invalid-feedback" role="alert">
                                {{ errors.attached_file }}</span>
            </div>
            <div class="d-flex btn-create-invoice justify-content-between">
                <button class="btn p-2 px-3"
                        @click.prevent="saveMethod(true)"
                        :disabled="is_processing"
                        :style="{border : '1px solid '+mainColor, color: mainColor, backgroundColor: 'white', fontSize: '14px'}">
                    Save draft
                </button>
                <button class="btn p-2 px-3"
                        :style="{backgroundColor: mainColor, color: 'white', fontSize: '14px'}"
                        @click.prevent="saveMethod()" :disabled="is_processing">
                    <template v-if="invoice.id">
                        Update invoice
                    </template>
                    <template v-else-if="!invoice.id">
                        Create invoice
                    </template>
                    <i v-if="is_processing" class="fas fa-spinner fa-spin"></i>
                </button>
            </div>
        </div>
        <div class="modal modal-create-customer fade" id="createCustomerModal" tabindex="-1" role="dialog" aria-labelledby="exportModalLabel"
             aria-hidden="true" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add customer</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                                :disabled="is_processing">
                            <img src="/images/delete_icon.svg" alt="delete">
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="col-12 col-sm-12 col-lg-6 mgb">
                                <label for="name">Name</label>
                                <input id="name" class="form-control" title="" v-model="customer.name" :class="{
                    'is-invalid' : errors.name
                }" placeholder="Customer Name" :disabled="is_processing">
                                <span class="invalid-feedback" role="alert">{{ errors.name }}</span>
                            </div>
                            <div class="col-12 col-sm-12 col-lg-6 mgb">
                                <label for="email">Email</label>
                                <input id="email" class="form-control" title="" v-model="customer.email" :class="{
                        'is-invalid' : errors.email
                    }" placeholder="Customer Email" :disabled="is_processing">
                                <span class="invalid-feedback" role="alert">{{ errors.email }}</span>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="col-12 col-sm-12 col-lg-6 mgb">
                                <label for="street">Street</label>
                                <input id="street" class="form-control" title="" v-model="customer.street" :class="{
                    'is-invalid' : errors.street
                }" placeholder="Customer Address Street" :disabled="is_processing">
                                <span class="invalid-feedback" role="alert">{{ errors.street }}</span>
                            </div>
                            <div class="col-12 col-sm-12 col-lg-6 mgb">
                                <label for="phone_number">Phone Number</label>
                                <input id="phone_number" type="tel" class="form-control" title="" v-model="customer.phone_number"
                                       :class="{
                        'is-invalid' : errors.phone_number
                    }" placeholder="Customer Phone Number" :disabled="is_processing">
                                <span class="invalid-feedback" role="alert">{{ errors.phone_number }}</span>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="col-12 col-sm-12 col-lg-6 mgb">
                                <label for="city">City</label>
                                <input id="city" class="form-control" title="" v-model="customer.city" :class="{
                        'is-invalid' : errors.city
                    }" placeholder="Customer City" :disabled="is_processing">
                                <span class="invalid-feedback" role="alert">{{ errors.city }}</span>
                            </div>
                            <div class="col-12 col-sm-12 col-lg-6 mgb">
                                <label for="state">State</label>
                                <input id="state" class="form-control" title="" v-model="customer.state" :class="{
                        'is-invalid' : errors.state
                    }" placeholder="Customer State" :disabled="is_processing">
                                <span class="invalid-feedback" role="alert">{{ errors.state }}</span>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="col-12 col-sm-12 col-lg-6 mgb">
                                <label for="country">Country</label>
                                <select id="country" class="custom-select" v-model="customer.country" :class="{
                                    'is-invalid' : errors.country
                                }" :disabled="is_processing">
                                    <option v-for="country in countries" :value="country.code">
                                        {{ country.name }}
                                    </option>
                                </select>
                                <span class="invalid-feedback" role="alert">{{errors.country}}</span>
                            </div>
                            <div class="col-12 col-sm-12 col-lg-6 mgb">
                                <label for="postal_code">Postal Code</label>
                                <input id="postal_code" class="form-control" title="" v-model="customer.postal_code"
                                       :class="{
                        'is-invalid' : errors.postal_code
                    }" placeholder="Customer Postal Code" :disabled="is_processing">
                                <span class="invalid-feedback" role="alert">{{ errors.postal_code }}</span>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="col-12 mgb">
                                <label for="description">Remark</label>
                                <textarea id="description" v-model="customer.remark" class="form-control description" rows="3" :class="{
                                        'is-invalid' : errors.remark
                                    }" placeholder="Tell more about this customer…" :disabled="is_processing"></textarea>
                                <span class="invalid-feedback" role="alert">{{ errors.remark }}</span>
                            </div>
                        </div>
                        <button class="btn"
                                :style="{backgroundColor: mainColor, color: 'white'}"
                                @click="createCustomer">
                            Add Customer
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal modal-create-customer fade" id="createTaxModal" tabindex="-1" role="dialog" aria-labelledby="exportModalLabel"
             aria-hidden="true" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add tax</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                                :disabled="is_processing">
                            <img src="/images/delete_icon.svg" alt="delete">
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="col-12 col-sm-12 col-lg-12 mgb">
                                <label for="name">Name<span class="text-danger">*</span></label>
                                <input id="name" class="form-control" title="" v-model="new_tax.name" :class="{
                    'is-invalid' : errors.new_tax_name
                }" placeholder="Name" :disabled="is_processing">
                                <span class="invalid-feedback" role="alert">{{ errors.new_tax_name }}</span>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="col-12 col-sm-12 col-lg-12 mgb">
                                <label for="rate">Rate<span class="text-danger">*</span></label>
                                <input id="rate" class="form-control" type="number" step="0.01" title="" v-model="new_tax.rate" :class="{
                    'is-invalid' : errors.new_tax_rate
                }" placeholder="%" :disabled="is_processing">
                                <span class="invalid-feedback" role="alert">{{ errors.new_tax_rate }}</span>
                            </div>
                        </div>
                        <button class="btn"
                                :style="{backgroundColor: mainColor, color: 'white'}"
                                @click="createTax">
                            Add Tax
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</template>

<script>
import Datepicker from "vuejs-datepicker";

export default {
    name: "Invoice",
    components: {
        Datepicker
    },
    props: {
        currency_list: {
            type: Object,
            required: true
        },
        zero_decimal_list: {
            type: Array,
        },
        business_logo: {
            type: String
        }
    },
    data() {
        return {
            business: [],
            customer: {
                id: null,
                name: '',
                email: '',
                phone_number: '',
                street: '',
                state: '',
                city: '',
                postal_code: '',
                country: '',
                remark: '',
            },
            customers_result: [],
            cycle_list: [],
            errors: {},
            is_processing: false,
            is_searching_product: false,
            invoice: {
                id: null,
                customer_id: null,
                reference: '',
                invoice_number: '',
                amount: '',
                amount_no_tax: '',
                email: '',
                due_date: '',
                invoice_date: '',
                currency: 'sgd',
                auto_invoice_number: false,
                products: [],
                tax_setting: '',
                memo: '',
                status: '',
                partial_payments: null,
                allow_partial_payments: false,
                file_path: null,
            },
            search_products_key: [],
            search_product: {
                keywords: '',
                search_results: [],
                timeout: null,

            },
            added_products: [{}],
            partial_payments: [{}],
            tax_list: [],
            search_customer: '',
            timeout: null,
            is_customer: true,
            create_customer_url: null,
            currency: 'sgd',
            currency_display: 'sgd',
            enable_tax_setting: false,
            total_amount: 0,
            attached_file: null,
            draft_mode: false,
            payment_by_products: true,
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
            countries: [],
            highlighted: {
                dates: [new Date()]
            },
            new_tax: {
                name: '',
                rate: '',
            }
        }
    },
    computed: {
        disableDates() {
            let date = this.invoice.invoice_date;
            // date.setDate(date.getDate() - 1);
            return {
                to: date
            }
        },
        disableDatesForPartialPayments() {
            let invoiceDate = this.invoice.invoice_date;
            let invoiceDueDate = this.invoice.due_date;

            let disableDates = {
                to: invoiceDate
            };

            if (invoiceDueDate) {
              const parseInvoiceDueDate = new Date(invoiceDueDate);

              parseInvoiceDueDate.setDate(parseInvoiceDueDate.getDate());

              disableDates = {
                to: invoiceDate,
                from: parseInvoiceDueDate,
              };
            }

            return disableDates;
        }
    },

    mounted() {
        this.countries = Countries.countries;
        if (window.Business)
            this.business = window.Business;
        if (window.Invoice) {
            this.invoice = {
                id: window.Invoice.id,
                customer_id: window.Invoice.customer_id,
                amount: window.Invoice.amount,
                amount_no_tax: window.Invoice.amount_no_tax,
                due_date: window.Invoice.due_date ? new Date((window.Invoice.due_date).replace(/-/g, "/")) : "",
                invoice_date: window.Invoice.invoice_date ? new Date((window.Invoice.invoice_date).replace(/-/g, "/")) : "",
                currency: window.Invoice.currency ?? "",
                reference: window.Invoice.reference ?? "",
                auto_invoice_number: false,
                products: [],
                tax_setting: window.Invoice.tax_settings_id ?? "",
                memo: window.Invoice.memo ?? "",
                status: window.Invoice.status,
                file_path: window.Invoice.attached_file ?? ""
            };
            this.invoice.invoice_number = window.Invoice.invoice_number;

            this.total_amount = parseFloat(this.invoice.amount_no_tax);

            if (this.invoice.tax_setting) {
                this.enable_tax_setting = true
            }

            if(window.Invoice.products.length == 0){
                this.payment_by_products = false;
            }

            this.addCustomer(window.Customer);
        }else {
            this.invoice.invoice_date = new Date();
            this.search_products_key[0] = "";
            this.invoice.invoice_number = "INV-";
            this.invoice.currency = this.business.currency;
        }
        if (window.Invoice && window.Invoice.products) {
            this.added_products = window.Invoice.products;
            this.added_products.forEach(function(prod, index, arr) {
                arr[index].variation.price = arr[index].variation.price;
            })
        }

        if (window.partialPayments) {
            this.partial_payments = window.partialPayments;
            this.partial_payments.forEach(function(part, index, arr) {
                arr[index].due_date = arr[index].due_date ? new Date(arr[index].due_date) : "";
            });
            this.invoice.allow_partial_payments = true;
        }

        this.tax_list = window.Tax_Settings;

        this.currency = Business.currency;

        this.currency_display = this.currency.toUpperCase();

        this.create_customer_url = this.getDomain('business/' + Business.id + '/customer/create', 'dashboard');
        this.customer.country = Business.country;
        if(!window.invoice){
            this.postHogOnlyCaptureData('invoice_initiate', '');
        }
    },

    methods: {
        checkDecimal() {
            if (this.invoiceAmount() != '' && this.zero_decimal_list.includes(this.invoice.currency)) {
                this.errors = {};
                if (/.*\.|,.*/.test(this.invoiceAmount())) {
                    this.errors = {
                        amount: this.invoice.currency + ' is zero-decimal currency',
                    };
                }
            }

        },

        checkSelectedCurrency() {
            if (!this.invoice.currency.length) {
                this.errors = {
                    currency: true,
                };
            }

        },
        checkProducts() {
            if (this.payment_by_products) {
                for (let item of this.added_products) {
                    if (item.quantity === '' || item.quantity === 0) {
                        this.errors.product_quantity = "Please fill in quantity for all products";
                        break;
                    }
                    if (!item.product) {
                        this.errors.product_exist = "Please choose product";
                        break;
                    }
                }
            }
        },
        checkTaxSetting() {
            if (this.enable_tax_setting && this.invoice.tax_setting === '') {
                this.errors.tax_setting = 'Please choose tax setting';
            }
        },
        checkReferences() {
            if(this.invoice.reference.length > 255){
                this.errors.reference = 'The reference may not be greater than 255 characters.'
            }
        },
        checkDescription() {
            if(this.invoice.memo.length > 255){
                this.errors.memo = 'The description may not be greater than 255 characters.'
            }
        },
        checkAmount(){
            if (this.total_amount === 0 || this.total_amount ===null)
                this.errors.amount = "Please specify amount."

            if(this.total_amount.toString().length > 10)
                this.errors.amount = "Maximum amount length is 10.";
        },
        checkAmountByProduct(){
            if (parseInt(this.invoiceAmount()) > 9999999) {
                this.errors.amount = "Maximum amount is 9999999.";
            }else if (parseInt(this.invoiceAmount()) <= 0 ) {
                this.errors.amount = "The amount should be greater than 0";
            }
        },
        checkPartialPayments(){
            let amount = 0;
            for (let item of this.partial_payments) {
                if (!item.amount || item.amount === '' || item.amount === 0) {
                    this.errors.partial_amount = "Amount is required";
                    break;
                }
                if (item.amount.toString().length > 6) {
                    this.errors.partial_amount = "Maximum amount length is 6.";
                }

                amount += parseFloat(item.amount);
            }
            if (parseFloat(this.invoiceAmount()) != parseFloat(amount.toFixed(2))){
                this.errors.partial_amount = "The sum of partial payment's amounts is not equal to total amount.";
            }

        },
        uploadFile(event) {
            let file = event.target.files[0];
            if (file.size > 1024 * 1024 * 2) {
                this.errors.attached_file = "Files should not be greater than 2 MB.";
                this.is_processing = false;
                return;
            }
            this.attached_file = event.target.files[0];
        },
        removeFile(event) {
            event.preventDefault();
            this.attached_file = null;
            this.invoice.file_path = null;
        },
        getTime(time) {
            if (typeof time != 'string') {
                let date = time.getDate() + '';
                if (date.length === 1) {
                    date = '0' + date;
                }
                let month = (time.getMonth() + 1) + '';
                if (month.length === 1) {
                    month = '0' + month;
                }
                return date + '/' + month + '/' + time.getFullYear();
            }

            return time;
        },
        createCustomer() {
            this.errors = {};

            axios.post(this.getDomain('v1/business/' + Business.id + '/customer', 'api'),
              this.customer,
              {
                withCredentials: true,
              }
            ).then(({data}) => {
                this.invoice.customer_id = data.id;
                this.customer = data;
                this.search_customer = '';
                this.customers_result = [];
                $('#createCustomerModal').modal('hide');
            }).catch(({response}) => {
                if (response.status === 422) {
                    _.forEach(response.data.errors, (value, key) => {
                        this.errors[key] = _.first(value);
                    });
                    this.$forceUpdate();
                }
            });
            this.postHogOnlyCaptureData('invoice_create_new_customer', '');
        },

        saveMethod(draft = false) {
            this.is_processing = true;
            this.errors = {};

            this.checkDecimal();
            this.checkSelectedCurrency();
            this.checkProducts();
            this.checkTaxSetting();
            this.checkReferences();
            this.checkDescription();
            this.validateDiscount();
            this.validateQuantity();
            if (!this.payment_by_products){
                this.checkAmount();
            }else{
                this.checkAmountByProduct()
            }

            if(this.invoice.allow_partial_payments)
                this.checkPartialPayments();

            if ((this.invoice.invoice_number === '' || this.invoice.invoice_number === 'INV-') && !this.invoice.auto_invoice_number) {
                this.errors.invoice_number = "Please specify invoice number.";
            }

            if(this.invoice.invoice_number.length > 20) {
                this.errors.invoice_number = "The description may not be greater than 20 characters."
            }

            if (this.invoice.customer_id === '' || this.invoice.customer_id ===null)
                this.errors.customer_id = "Customer is required.";

            if (Object.keys(this.errors).length > 0) {
                this.is_processing = false;
                this.showError(_.first(Object.keys(this.errors)));
                return;
            }

            this.invoice.email = this.customer ? this.customer.email : '';

            this.invoice.due_date = this.getTime(this.invoice.due_date);
            this.invoice.invoice_date = this.getTime(this.invoice.invoice_date);

            for (let [index, val] of this.partial_payments.entries()) {
                if (this.partial_payments[index].due_date)
                    this.partial_payments[index].due_date = this.getTime(this.partial_payments[index].due_date);
            }

            if (this.payment_by_products) {
                _.forEach(this.added_products, (item) => {
                    this.invoice.products.push({
                        'variation_id': item.variation.id,
                        'quantity': item.quantity,
                        'discount': item.discount
                    })
                });
            }
            if (this.invoice.products && this.invoice.products.length > 0)
                this.invoice.products = JSON.stringify(this.invoice.products);
            else this.invoice.products = null;

            if (this.partial_payments && this.partial_payments.length > 0)
                this.invoice.partial_payments = JSON.stringify(this.partial_payments);
            else this.invoice.partial_payments = null;

            this.invoice.amount = this.invoiceAmount();

            if (draft)
                this.invoice.status = 'draft';

            let formData = new FormData();

            if (this.attached_file) {
                formData.append('attached_file', this.attached_file);
            }

            formData.append('invoice', JSON.stringify(this.invoice));

            axios.post(this.getDomain('business/' + Business.id + '/invoice', 'dashboard'), formData, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'multipart/form-data'
                }
            })
                .then(({data}) => {
                   window.location.href = data.redirect_url;
                }).catch(({response}) => {
                if (response.status === 422) {
                    _.forEach(response.data.errors, (value, key) => {
                        this.errors[key] = _.first(value);
                    });
                    this.invoice.products = [];
                    this.invoice.invoice_number = this.invoice.invoice_number;
                    this.showError(_.first(Object.keys(this.errors)));
                }
            });

            this.postHogCaptureData('invoice_created', {
                'invoice_fixed_price': this.invoice.price,
                'invoice_products': this.invoice.product,
                'invoice_partial_payments': this.invoice.partialPayments? true: false,
                'Invoice_full_payments': this.invoice.partialPayments? false: true
            })

        },

        disallowDecimal(event) {
            if (event.keyCode === 190) {
                event.preventDefault();
            }
        },

        showError(firstErrorKey) {
            if (firstErrorKey !== undefined) {
                this.scrollTo('#' + firstErrorKey);

                $('#' + firstErrorKey).focus();
            }

            this.is_processing = false;
        },

        searchCustomer() {
            clearTimeout(this.timeout);

            this.timeout = setTimeout(() => {
                if (this.search_customer === '') {
                    this.customers_result = [];
                } else {
                    axios.post(this.getDomain('business/' + Business.id + '/point-of-sale/customer', 'dashboard'), {
                        keywords: this.search_customer,
                    }).then(({data}) => {
                        this.customers_result = data;
                    });
                }
            }, 500);
        },

        addCustomer(customer) {
            this.invoice.customer_id = customer.id;
            this.customer = customer;
            this.search_customer = '';
            this.customers_result = [];
        },

        removeCustomer(id) {
            this.invoice.customer_id = null;
            this.customer = {
                id: null,
                name: '',
                email: '',
                phone_number: '',
                street: '',
                state: '',
                city: '',
                postal_code: '',
                country: '',
                remark: '',
            };
            this.search_customer = '';
            this.customers_result = [];
        },

        removeDueDate(){
            this.invoice.due_date = '';

            this.partial_payments.map(function(partialPayment) {
                partialPayment.due_date = null;
            });
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
                this.added_products[key].quantity = 1;
                this.added_products[key].discount = 0;
                this.search_products_key[key]= '';
                this.$forceUpdate();
            }
        },

        removeProduct(event, key) {
            event.preventDefault();

            this.added_products.splice(key, 1);
        },
        removePayment(event, key) {
            event.preventDefault();

            this.partial_payments.splice(key, 1);
        },
        removeTax( event){
            event.preventDefault();
            this.enable_tax_setting = false;
        },
        isNumber(evt) {
            evt = (evt) ? evt : window.event;
            var charCode = (evt.which) ? evt.which : evt.keyCode;
            if ((charCode > 31 && (charCode < 48 || charCode > 57)) && charCode !== 46) {
                evt.preventDefault();
                ;
            } else {
                return true;
            }
        },
        checkDiscount(item) {
            let total = item.variation.price * item.quantity;
            let discount = item.discount;

            this.errors.product_discount =  null;
            if(discount > total) {
                this.errors.product_discount = "The discount can be lower than the total";
            }
        },
        validateDiscount() {
            this.added_products.forEach((item) => {
                let discount = item.discount ?? 0;
                let price = item.variation ? item.variation.price : 0;
                let quantity = item.quantity ?? 0;
                let total = price * quantity;
                if(parseInt(discount) > parseInt(total)) {
                    this.errors.product_discount = "The discount can be lower than the total";
                }
            });
        },
        validateQuantity() {
            this.added_products.forEach((item) => {
                let quantity = item.quantity ?? 0;
                let quantity_available = item.variation ? item.variation.quantity : 0;
                if(parseInt(quantity) > parseInt(quantity_available)) {
                    this.errors.product_quantity = "The quantity is greater than quantity available";
                }
            });
        },
        itemPriceWithDiscount(item) {
            return (item.variation.price * item.quantity) - (item.discount ?? 0);
        },
        invoiceAmount() {
            let amount = this.total_amount != '' ? this.total_amount : 0;

            if (this.added_products.length > 0 && this.payment_by_products) {
                amount = 0;
                this.added_products.forEach((item) => {
                    amount += item.product ? this.itemPriceWithDiscount(item) : 0;
                })
            }

            this.invoice.amount_no_tax = amount.toFixed(2);

            if (this.invoice.tax_setting && this.enable_tax_setting) {
                let tax = this.tax_list.find(x => x.id === this.invoice.tax_setting);

                amount += amount * tax.rate / 100;
                if (this.zero_decimal_list.includes(this.invoice.currency)) {
                    amount = Math.ceil(amount);
                }
            }
            if (this.zero_decimal_list.includes(this.invoice.currency))
                return amount;

            return isNaN(parseFloat(amount)) ? '' : amount.toFixed(2);
        },
        taxAmount() {
            if (this.invoice.tax_setting && this.enable_tax_setting) {
                let amount = this.total_amount;

                if (this.added_products.length > 0 && this.payment_by_products) {
                    amount = 0;
                    this.added_products.forEach((item) => {
                        amount += item.product ? this.itemPriceWithDiscount(item) : 0;
                    })
                }
                let tax = this.tax_list.find(x => x.id === this.invoice.tax_setting);

                return (amount * tax.rate / 100).toFixed(2);
            } else return 0;
        },
        changeAutoInvoice() {
            if(this.invoice.auto_invoice_number == false) {
                this.invoice.invoice_number = "INV-";
            }else{
                this.invoice.invoice_number = "";
            }
        },
        createTax() {
            this.is_processing = true;
            this.errors = {};

            if(this.new_tax.name === '') {
                this.errors.new_tax_name = "The name is required ."
            }

            if(this.new_tax.name.length > 255) {
                this.errors.new_tax_name = "The ame may not be greater than 255 characters."
            }

            if(this.new_tax.rate === '' || this.new_tax.rate === 0) {
                this.errors.new_tax_rate = "The rate is required."
            }

            if(this.new_tax.rate.length > 2) {
                this.errors.new_tax_rate = "The rate may not be greater than 2 characters."
            }

            if (Object.keys(this.errors).length > 0) {
                this.is_processing = false;
                this.showError(_.first(Object.keys(this.errors)));
                return;
            }

            axios.post(this.getDomain('business/' + Business.id + '/tax-setting', 'dashboard'), this.new_tax).then(({data}) => {
               this.new_tax.name = ''
               this.new_tax.new_tax_name = ''

               this.is_processing = false;
               this.tax_list.push(data[0]);

               $('#createTaxModal').modal('hide');

            }).catch(({response}) => {
                if (response.status === 422) {
                    _.forEach(response.data.errors, (value, key) => {
                        this.errors[key] = _.first(value);
                    });
                    this.$forceUpdate();
                }
                this.is_processing = false;
            });
        }
    },
}
</script>
