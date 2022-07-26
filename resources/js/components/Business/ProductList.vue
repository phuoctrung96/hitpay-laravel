<style scoped>
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
        <div v-if="products.length > 0">
            <div class="card-body border-top border-bottom px-4 py-0 action-panel">
                <div class="d-flex inner align-items-center">
                    <input type="checkbox" class="all-status-checkbox mr-3" v-model="checkedAll" @click="checkAll()">
                    <button class="btn btn-danger btn-sm" @click="deleteProducts">Delete Products</button>
                </div>
            </div>
            <div v-for="(product) in products" :key="product.id" class="is-item-product">
                <input type="checkbox" class="is-product-checkbox" v-model="product.checked" id="check_product">
                <a class="hoverable"
                :href="productLink(product.id, this)">
                <div class="item-product">
                    <div class="card-body px-4 position-relative">
                            <div class="row">
                                <div class="col-12 col-lg-2">
                                    <div class="d-flex">
                                        <div class="thumbnail">
                                            <img :src="getImage(product.images)"
                                            :alt="product.name">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-lg-8">
                                    <div class="product-detail">
                                        <p class="title fw-500 mb-2">{{ product.name }} <span v-if="product.shopify">( <img src="/images/shopify.svg" alt="shopify"> )</span></p>
                                        <p v-if="product.description" v-html="showDescription(product.description)" class="description"></p>
                                        <div class="meta-detail d-flex">
                                            <p v-if="product.is_manageable && product.quantity" class="quantity mr-3 mb-0">Quantity Available:
                                                <span class="number">{{ product.quantity }}</span>
                                            </p>
                                            <p v-else-if="product.is_manageable && product.variations" class="quantity mr-3 mb-0">Quantity Available:
                                                <span class="number">{{ product.variations.reduce((sum, variant) => sum + variant.quantity, 0) }}</span>
                                            </p>
                                            <p v-else class="quantity mr-3 mb-0">Quantity Available:
                                                <span class="number"> - </span>
                                            </p>
                                            <p v-if="product.category_id" class="cat mb-0">Product category:
                                                <span class="name">{{ product.category_id[0].name }}</span>
                                            </p>
                                            <p v-else class="cat mb-0">Product category:
                                                <span class="name"> - </span>
                                            </p>
                                            <template v-if="product.variations_count > 0 && product.variations[0].stock_keeping_unit">
                                                <p class="text-dark small mb-0">Stock Keeping Unit:
                                                    <span class="text-muted">{{ product.variations[0].stock_keeping_unit }}</span>
                                                </p>
                                            </template>
                                        </div>
                                        <template v-if="product.shopify">
                                            <p class="text-dark small mb-0">
                                                <i class="fab fa-shopify mr-2"></i> Synced from Shopify</p>
                                        </template>
                                    </div>
                                </div>
                                <div class="col-12 col-lg-2">
                                    <div class="meta d-flex justify-content-between align-items-center">
                                        <div class="status" :class="(product.status == 'draft') ? 'draft' : ''">
                                            <span>{{ product.status }}</span>
                                        </div>
                                        <span class="price font-weight-bold">{{ product.price_display }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                </div>
                </a>
            </div>
            <div class="pagination-product">
                <div class="card-body px-4">
                    <b-pagination
                        v-model="page"
                        :total-rows="total"
                        :per-page="pageSize"
                        first-text="First"
                        prev-text="Prev"
                        next-text="Next"
                        last-text="Last"
                        @change="handlePageChange">
                    </b-pagination>
                </div>
            </div>
        </div>
        <div v-else>
            <div class="card-body bg-light p-4">
            <div class="text-center text-muted py-4">
                <p><i class="fa fas fa-boxes fa-4x"></i></p>
                <p class="small mb-0">- No product found -</p>
            </div>
            </div>
        </div>
        <div class="modal modal-succeed" tabindex="-1" role="dialog" id="confirmationModal">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-body bg-light text-center">
                        <div class="icon d-flex align-items-center mt-3 mb-3">
                            <img src="/images/ico-done.png" alt="">
                        </div>
                        <h3 class="mb-3">Done!</h3>
                        <p class="mb-4">Products have been successfully deleted</p>
                        <p><a data-dismiss="modal" href="#" class="btn btn-primary">OK</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
      products: {
          type: Array,
          default: ()=>[]
      },
      total: {
          type: Number,
          default: 0
      },
      page: {
          type: Number,
          default: 1
      },
      pageSize: {
          type: Number,
          default: 5
      }
    },
    data() {
        return {
            business: [],
            is_processing: false,
            checkedAll: false
        };
    },
    mounted() {
        this.business = Business;
    },
    methods: {
        productLink(id) {
            return '/business/' + this.business.id + '/product/' + id;
        },
        showDescription(desc) {
            return desc.substring(0, 50);
        },
        getImage(images) {
            if (images.length > 0) {
              let img = images[0]['other_dimensions'].find(img => img.size === 'thumbnail');

              if (img) return img.path;
            }

            return '/hitpay/images/product.jpg';
        },
        checkAll() {
            _.each(this.products, (product) => {
                product.checked = !this.checkedAll;
            });
        },
        async deleteProducts() {
            let selectedProducts = this.products.filter((product, index) => true === product.checked);

            if (selectedProducts.length < 1) {
                alert('Nothing to delete.');
                return;
            }
            let submissionData = {
                'products': selectedProducts,
                'status' : this.status
            };
            await axios.post(this.getDomain('business/' + this.business.id + '/product/delete-products', 'dashboard'), submissionData).then(({data}) => {
                this.retrieveItems();
                $('#confirmationModal').modal();
            });
        },
        retrieveItems() {
            let i = 0;
            while(i < this.products.length) {
                if(this.products[i].checked) {
                    this.products.splice(i, 1);
                } else {
                    i++
                }
            }
        },
        handlePageChange(value) {
            this.$emit('handlePageChange', value);
        }
    },
}
</script>
