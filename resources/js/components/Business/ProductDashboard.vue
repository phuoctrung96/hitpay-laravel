<style scoped>
.product-checkbox {
    position: absolute;
    top: 5px;
    left: 5px;
}

.action-panel {
    padding-left: 6px;
}
.ui.selection.dropdown {
    min-width: 12em;
}
</style>
<template>
    <div class="product-filters">
        <div class="card border-0 shadow-sm">
            <div class="card-body px-4 pb-2">
                <h3>Products</h3>
                <product-url-settings></product-url-settings>
                <div class="meta-search">
                    <div class="row row-meta-search">
                        <div class="col col-lg-7 col-search">
                            <div class="form-group">
                                <div class="input-group">
                                    <input class="form-control" placeholder="Search Product"
                                    title="Search Product" name="keywords" value="" v-model="keywords">
                                    <div class="input-group-append">
                                        <button class="btn btn-secondary" @click="searchProduct()">Search</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col col-lg-5 col-button">
                            <div class="row">
                                <div class="col col-lg-5">
                                    <button class="btn btn-primary d-block mgb btn-plus white-icon"  @click="redirectAddProduct()"><span>Add Products</span></button>
                                </div>
                                <div class="col col-lg-7">
                                    <button class="btn btn-outline-primary btn-plus" @click="redirectAddProductBulk()"><span>Add Products In Bulk</span></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="search-label clearfix">
                    <template v-for="(item, index) in selected_status">
                        <div class="form-group" :key="index">
                            <div class="input-group">
                                <label class="is-label-delete d-flex">
                                    <span class="mr-2">{{item.text}}</span>
                                    <span class="delete"><img src="/images/delete_gray_icon.svg" alt="" @click="removeStatus(index)"></span>                                   
                                </label>
                            </div>
                        </div>
                    </template>
                    <template v-for="(item, index) in selected_category">
                        <div class="form-group" :key="index">
                            <div class="input-group">
                                <label class="is-label-delete d-flex">
                                    <span class="mr-2">{{item.text}}</span>
                                    <span class="delete"><img src="/images/delete_gray_icon.svg" alt="" @click="removeCategory(index)"></span>                                   
                                </label>
                            </div>
                        </div>
                    </template>
                    <template v-for="(item, index) in selected_inventory">
                        <div class="form-group" :key="index">
                            <div class="input-group">
                                <label class="is-label-delete d-flex">
                                    <span class="mr-2">{{item.text}}</span>
                                    <span class="delete"><img src="/images/delete_gray_icon.svg" alt="" @click="removeInventory(index)"></span>                                   
                                </label>
                            </div>
                        </div>
                    </template>
                    <template v-for="(item, index) in selected_source">
                        <div class="form-group" :key="index">
                            <div class="input-group">
                                <label class="is-label-delete d-flex">
                                    <span class="mr-2">{{item.text}}</span>
                                    <span class="delete"><img src="/images/delete_gray_icon.svg" alt="" @click="removeSource(index)"></span>                                   
                                </label>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            <div class="main-product border-top">
                <div class="dropdown-option">
                    <div class="card-body px-4 py-0">
                        <div class="row mt-2">
                            <div class="col">
                                <sui-dropdown
                                    class="form-control is-dropdown"
                                    placeholder="Status"
                                    selection
                                    :options="list_status"
                                    v-model="current_status"/>
                            </div>
                            <div class="col">
                                <sui-dropdown
                                    class="form-control is-dropdown"
                                    placeholder="Category"
                                    selection
                                    :options="categories"
                                    v-model="current_category"/>
                            </div>
                            <div class="col">
                                <sui-dropdown
                                    class="form-control is-dropdown"
                                    placeholder="Inventory"
                                    selection
                                    :options="inventories"
                                    v-model="current_inventory"/>
                            </div>
                            <div class="col">
                                <sui-dropdown
                                    class="form-control is-dropdown"
                                    placeholder="Source"
                                    selection
                                    :options="sources"
                                    v-model="current_source"/>
                            </div>
                            <div class="col col-button">
                                <div class="mgl">
                                    <business-product-export :current_business_user="current_user"></business-product-export>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="all-products">
                    <business-product-list v-if="is_processing" :products="products" :total="total" :page="page" @handlePageChange="handlePage($event)"></business-product-list>
                </div>
            </div>
        </div>
    </div>
</template>

<script>

export default {
    name: 'BusinessProductDashboard',
    props: {
      current_user: Object,
    },
    data() {
        return {
            business: [],
            products: [],
            is_processing: true,
            current_status: null,
            list_status: [
                {   text: "Published",
                    value: "published"  
                },
                {   text: "Draft",
                    value: "draft"  
                }
            ],
            selected_status: [],
            categories: [],
            current_category: null,
            selected_category: [],
            inventories:[
                {
                    text: "In stock",
                    value: "in_stock"
                },
                {
                    text: "Out of stock",
                    value: "out_of_stock"
                }
            ],
            selected_inventory: [],
            current_inventory: null,
            sources: [
                {
                    text: "Shopify",
                    value: "shopify"
                },  
                {
                    text: "WooCommerce",
                    value: "wooCommerce"
                },
            ],
            selected_source: [],
            current_source: null,
            keywords: '',
            page: 1,
            pageSize: 5,
            total: 0
        };
    },
    mounted() {
        this.business = Business;
        this.getCategory();
    },
    watch: {
        current_status: {
            handler(){
                this.selectStatus();
            }
        },
        current_category: {
            handler(){
                this.selectCategory();
            }
        },
        current_inventory: {
            handler(){
                this.selectInventory();
            }
        },
        current_source: {
            handler(){
                this.selectSource();
            }
        }
    },
    methods: {
        redirectAddProduct(){
            window.location.href = this.getDomain(`business/${ this.business.id }/product/create`, "dashboard");
        },
        redirectAddProductBulk(){
            window.location.href = this.getDomain(`business/${ this.business.id }/product/create-in-bulk`, "dashboard");
        },
        getRequestParams() {
            let params = {'with': 'variations,images'};
            if(this.keywords && this.keywords != '')
                params["keywords"] = this.keywords;

            if(this.selected_status.length > 0){
                let status = [];
                this.selected_status.forEach(item => {
                    status.push(item.value);
                });
                params["statuses"] = status; 
            }

            if(this.selected_category.length > 0) {
                let category = []
                this.selected_category.forEach(item => {
                    category.push(item.value);
                })
                params["categories"] = category;
            }

            if(this.selected_inventory.length == 1) {
                params["inventory"] = this.selected_inventory[0].value; 
            }

            if(this.selected_source.length > 0) {
                let source = [];
                this.selected_source.forEach(item => {
                    source.push(item.value);
                });
                params["sources"] = source; 
            }

            params["page"] = this.page;
            params["perPage"] = this.pageSize;

            return params;
        },
        async searchProduct() {
            this.is_processing = false;
            await axios.get(this.getDomain(`v1/business/${this.business.id}/products`, 'api'), {
              params: this.getRequestParams(),
              withCredentials: true
            }).then((response) => {
                this.products = response.data.data;
                this.total = response.data.meta.total;
                this.is_processing = true;
            }).catch((e) => {
              this.is_processing = false;
              console.log(e);
            });
        },
        getCategory() {
            this.categories = [];
            axios.get(this.getDomain(`v1/business/${this.business.id}/product-category`, 'api'), {
              withCredentials: true
            }).then((response) => {
                let categories = response.data;
                if(categories) {
                    categories.forEach(item => {
                        this.categories.push({text: item.name, value: item.id});
                    });
                    this.current_category = null;
                    this.searchProduct();
                }              
            })
        },
        selectCategory() {
            if(this.current_category == null)
                return;

            if(this.selected_category.length > 0 ){
                this.categories.forEach(item => {
                    if(item.value == this.current_category && !this.selected_category.includes(item)) {
                        this.selected_category.push(item);
                    }
                });
            } else {
                this.categories.forEach(item => {
                    if(item.value == this.current_category) {
                        this.selected_category.push(item);
                    } 
                });
            }
            
            this.current_category = null;
            this.searchProduct();
            this.sendPostHog();
        },
        selectStatus() {
            if(this.current_status == null)
                return;
            
            if(this.selected_status.length > 0 ){
                this.list_status.forEach(item => {
                    if(item.value == this.current_status && !this.selected_status.includes(item)) {
                        this.selected_status.push(item);
                    }
                });
            } else {
                this.list_status.forEach(item => {
                    if(item.value == this.current_status) {
                        this.selected_status.push(item);
                    } 
                });
            }

            this.current_status = null;
            this.searchProduct();

            this.sendPostHog();
        },
        selectInventory() {
            if(this.current_inventory == null)
                return;
            
            if(this.selected_inventory.length > 0 ){
                this.inventories.forEach(item => {
                    if(item.value == this.current_inventory && !this.selected_inventory.includes(item)) {
                        this.selected_inventory.push(item);
                    }
                });
            } else {
                this.inventories.forEach(item => {
                    if(item.value == this.current_inventory) {
                        this.selected_inventory.push(item);
                    } 
                });
            }

            this.current_inventory = null;
            this.searchProduct();
            this.sendPostHog();
        },
        selectSource() {
            if(this.current_source == null)
                return;

            if(this.selected_source.length > 0 ){
                this.sources.forEach(item => {
                    if(item.value == this.current_source && !this.selected_source.includes(item)) {
                        this.selected_source.push(item);
                    }
                });
            } else {
                this.sources.forEach(item => {
                    if(item.value == this.current_source) {
                        this.selected_source.push(item);
                    } 
                });
            }

            this.current_source = null;
            this.searchProduct();
            this.sendPostHog();
        },
        handlePage(page){
            this.page = page;
            this.searchProduct();
        },
        removeStatus(index){
            this.selected_status.splice(index, 1);
            this.page = 1;
            this.searchProduct();
            this.sendPostHog();
        },
        removeCategory(index){
            this.selected_category.splice(index, 1);
            this.page = 1;
            this.searchProduct();

            if(this.selected_category.length == 0)
                this.sendPostHog();
        },
        removeInventory(index){
            this.selected_inventory.splice(index, 1);
            this.page = 1;
            this.searchProduct();
            this.sendPostHog();
        },
        removeSource(index){
            this.selected_source.splice(index, 1);
            this.page = 1;
            this.searchProduct();
            this.sendPostHog();
        },
        handlePage(page){
            this.page = page;
            this.searchProduct();
        },
        sendPostHog(search_word='', published=false, draft=false, category=false, in_stock=false, out_of_stock=false, shopify=false, woocommerce=false) {
            if(this.keywords != '')
                search_word = this.keywords

            this.selected_status.forEach(item => {
                if(item.value == 'draft'){
                    draft = true;
                }else{
                    published = true;
                }
            });

            if(this.selected_category.length > 0)
                category = true;
            
            if(this.selected_inventory.forEach(item => {
                if(item.value == 'in_stock'){
                    in_stock = true;
                }else if(item.value == 'out_of_stock'){
                    out_of_stock = true;
                }
            }));

            if(this.selected_source.forEach(item => {
                if(item.value == 'shopify'){
                    shopify = true;
                }else if(item.value == 'wooCommerce'){
                    woocommerce = true;
                }
            }));

            this.postHogOnlyCaptureData('product_filter', {
                search_word: search_word,
                published: published,
                draft: draft,
                category: category,
                in_stock: in_stock,
                out_of_stock: out_of_stock,
                shopify: shopify,
                woocommerce: woocommerce
            });
        }
    }
}
</script>
