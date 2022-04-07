<style scoped>
.pagination {
    display: block !important;
}

.product-checkbox {
    position: absolute;
    top: 5px;
    left: 5px;
}

.action-panel {
    padding-left: 6px;
}
</style>
<template>
    <div>
        <div class="card-body border-top py-2 action-panel">
            <input type="checkbox" class="all-status-checkbox mr-3" v-model="checkedAll" @click="checkAll()">
            <button class="btn btn-danger btn-sm" @click="deleteProducts">Delete Products</button>
        </div>
        <a class="hoverable" v-for="(product,index) in pageOfProducts" :key="product.id"
           :href="productLink(product.id)">
            <div class="card-body bg-light border-top p-4 position-relative">
                <input type="checkbox" class="product-checkbox"
                       v-model="product.checked">
                <div class="media">
                    <template v-if="product.images_count > 0">
                        <img :src="product.imageSrc"
                             class="d-none d-phone-block listing rounded border mr-3"
                             :alt="product.name">
                    </template>
                    <template v-else>
                        <img src="/hitpay/images/product.jpg"
                             class="d-none d-phone-block listing rounded border mr-3"
                             alt="product">
                    </template>
                    <div class="media-body">
                        <span class="font-weight-bold text-dark float-right">{{ product.showPrice }}</span>
                        <p class="font-weight-bold mb-2">{{ product.name }}</p>
                        <p class="text-dark small mb-2">
                            <span class="text-muted"># {{ product.id }}</span></p>
                        <p v-if="product.description" class="text-dark small mb-0">
                            {{ showDescription(product.description) }}</p>
                        <template v-if="product.variations_count > 1">
                            <p class="text-dark small mb-0">Variations Count:
                                <span class="text-muted">{{ product.variations_count }}</span></p>
                        </template>

                        <p v-if="product.manageable" class="text-dark small mb-0">Quantity Available:
                            <span
                                class="text-muted">{{ product.quantity }}</span>
                        </p>
                        <template v-if="product.stock_keeping_unit">
                            <p class="text-dark small mb-0">Stock Keeping Unit:
                                <span class="text-muted">{{ product.stock_keeping_unit }}</span></p>
                        </template>
                        <template v-if="product.shopify_id">
                            <p class="text-dark small mb-0">
                                <i class="fab fa-shopify mr-2"></i> Synced from Shopify</p>
                        </template>
                    </div>
                </div>
            </div>
        </a>
        <jw-pagination :items="products" :pageSize=5 @changePage="onChangePage"></jw-pagination>
        <div class="modal" tabindex="-1" role="dialog" id="confirmationModal">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-content">
                        <div class="modal-body">
                            <p>Products have been successfully deleted</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
      status: String,
    },
    data() {
        return {
            business: [],
            products: [],
            is_processing: false,
            pageOfProducts: [],
            checkedAll: false,
        };
    },
    mounted() {
        this.business = Business;

        if (window.Products !== undefined) {
            this.products = Products;
        }

        let productsWithAttrs = [];

        let productAttrs = ProductAttrs;

        let i = 0;
        _.each(this.products, (product) => {
            product.checked = false;
            product.imageSrc = productAttrs['image'][i];
            product.showPrice = productAttrs['price'][i];
            product.manageable = productAttrs['manageable'][i];
            product.quantity = productAttrs['quantity'][i];
            productsWithAttrs.push(product);
            i++;
        });

        this.products = productsWithAttrs;
    },
    methods: {
        onChangePage(pageOfItems) {
            // update page of items
            this.pageOfProducts = pageOfItems;
        },
        productLink(id) {
            return '/business/' + this.business.id + '/product/' + id;
        },
        checkAll() {
            _.each(this.pageOfProducts, (product) => {
                if (!this.checkedAll) {
                    product.checked = true;
                } else product.checked = false;
            });
        },
        showDescription(desc) {
            return desc.substring(0, 50);
        },

        async deleteProducts() {
            let deleteStateCount = this.pageOfProducts.reduce((sum, product) => !product.checked ? sum : sum + 1, 0);
            if (deleteStateCount < 1) {
                alert('Nothing to delete.');
                return;
            }
            let submissionData = {
                'products': this.pageOfProducts,
                'status' : this.status
            };
            await axios.post(this.getDomain('business/' + this.business.id + '/product/delete-products', 'dashboard'), submissionData).then(({data}) => {
                $('#confirmationModal').modal();
                this.products = data.products;

                let productAttrs = data.product_attrs;

                let productsWithAttrs = [];

                let i = 0;
                _.each(this.products, (product) => {
                    product.checked = false;
                    product.imageSrc = productAttrs['image'][i];
                    product.showPrice = productAttrs['price'][i];
                    product.manageable = productAttrs['manageable'][i];
                    product.quantity = productAttrs['quantity'][i];
                    productsWithAttrs.push(product);
                    i++;
                });

                this.products = productsWithAttrs;
            });
        },

    },
}
</script>
