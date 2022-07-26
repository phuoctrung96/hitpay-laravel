<template>
    <div>
        <h5>Sync products and inventory between Shopify and HitPay</h5>
        <p><a href="https://hitpay.zendesk.com/hc/en-us/articles/5929906750233-How-do-I-sync-products-inventory-and-orders-between-HitPay-and-Shopify-" target="_blank">See how it works</a></p>
        <div class="card" v-if="hotglueLinkedDetails.length>0">
            <div class="tbl-sync-list p-2">
                <table>
                    <tbody>
                        <tr v-for="(hotglueIntegration,index) in hotglueLinkedDetails" :key="index" :class="{ 'border-bottom' :  index + 1 < hotglueLinkedDetails.length }">
                            <td style="width: 16%">
                                <button class="btn bg-default btn-lg btn-icon">
                                    <svg
                                        height="25"
                                        viewBox="0 0 576 144">
                                        <use xlink:href='/images/hitpay.svg#hitpay'></use>
                                    </svg>
                                </button>
                            </td>
                            <td align="center" style="width: 6%">
                                <i class="fas fa-3x fa-long-arrow-alt-left color-gray" v-if="hotglueIntegration.type == 'ecommerce'"></i>
                                <i class="fas fa-3x fa-long-arrow-alt-right color-gray" v-else></i>
                            </td>
                            <td align="right" style="width: 16%">
                                <button class="btn bg-gray btn-lg btn-icon">
                                    <img src="/images/shopify.svg" alt="shopify">
                                </button>
                            </td>
                            <td align="center" style="width: 15%">
                                <p class="mb-0">Status</p>
                                <span class="badge bg-success text-white" v-if="hotglueIntegration.connected">Active</span>
                                <span class="badge bg-danger text-white" v-else>Inactive</span>
                            </td>
                            <td style="width: 15%">
                                <p class="mb-0">Last Synced On</p>
                                <p class="mb-0 small pr-2 pt-1">{{ (hotglueIntegration.job_in_progress_job_created.length > 0 || hotglueIntegration.job_in_progress_job_queued.length > 0) && (hotglueIntegration.selected_location_id || hotglueIntegration.hotglue_location.length === 0) ? 'Sync in progress' : formatDate(hotglueIntegration.last_sync_date) }}</p>
                            </td>
                            <td align="center" style="width: 16%">
                                <div v-if="hotglueIntegration.connected">
                                    <div v-if="(hotglueIntegration.job_in_progress_job_created.length > 0 || hotglueIntegration.job_in_progress_job_queued.length > 0 || (hotglueIntegration.job_done && hotglueIntegration.job_done[0].status === 'SYNCED')) && (hotglueIntegration.selected_location_id || hotglueIntegration.hotglue_location.length === 0)">
                                        <div v-if="isSyncCompleted(hotglueIntegration, index)">
                                            <p class="mb-0">Sync</p>
                                            <span class="badge bg-danger text-white">completed</span>
                                        </div>
                                        <div v-else>
                                            <p class="mb-0">Progress</p>
                                            <div class="progress mb-3">
                                                <div id="progress-bar" class="progress-bar progress-bar-animated progress-bar-striped" role="progressbar" v-bind:style="{ width: hotglueIntegration.progress_percent + '%' }" v-bind:aria-valuenow="hotglueIntegration.progress_percent" aria-valuemin="0" aria-valuemax="100">
                                                    {{ hotglueIntegration.progress_percent }}%
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else-if="!hotglueIntegration.selected_location_id && hotglueIntegration.hotglue_location.length > 0">
                                        <div class="form-group">
                                            <label>Inventory Location</label>
                                            <select class="form-control" @change="saveInventoryLocation($event, hotglueIntegration.id)">
                                                <option selected disabled>Choose ...</option>
                                                <option v-for="location in hotglueIntegration.hotglue_location" :key="location.ecommerce_location_id" :value="location.ecommerce_location_id">
                                                    {{ location.name }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div v-else>
                                        <p class="mb-0">Sync</p>
                                        <span class="badge bg-danger text-white">{{ hotglueIntegration.job_done[0].status }}</span>
                                    </div>
                                </div>
                            </td>
                            <td align="right" style="width: 16%">
                                <div v-if="hotglueIntegration.connected">
                                    <div v-if="(hotglueIntegration.job_in_progress_job_created.length === 0 && hotglueIntegration.job_in_progress_job_queued.length === 0) && (hotglueIntegration.selected_location_id || hotglueIntegration.hotglue_location.length === 0)">
                                        <button class="btn bg-default btn-sm mb-0" align="right" @click.prevent="syncNow(hotglueIntegration)">Sync Now</button>
                                        <p class="mb-0 small pr-2 pt-1 disconnect" @click.prevent="disconnect(hotglueIntegration.id, hotglueIntegration.type)" v-if="hotglueIntegration.type == 'ecommerce'"><i class="fas fa-spinner fa-spin" v-if="disconnectEcommerceLoading"></i> Disconnect</p>
                                        <p class="mb-0 small pr-2 pt-1 disconnect" @click.prevent="disconnect(hotglueIntegration.id, hotglueIntegration.type)" v-else><i class="fas fa-spinner fa-spin" v-if="disconnectProductLoading"></i> Disconnect</p>
                                    </div>
                                    <div v-else-if="!hotglueIntegration.selected_location_id && hotglueIntegration.hotglue_location.length > 0">
                                    </div>
                                    <div v-else>
                                        <button class="btn bg-default btn-sm mb-0 animatedDots" align="right">Syncing</button>
                                    </div>
                                </div>
                                <div v-else>
                                    <button class="btn bg-default btn-sm" align="right" @click.prevent="connectEcommerceFlow" v-if="hotglueIntegration.type == 'ecommerce'"><i class="fas fa-spinner fa-spin" v-if="loadingEcommerce"></i> Enable</button>
                                    <button class="btn bg-default btn-sm" align="right" @click.prevent="connectProductFlow(hotglueIntegration.source)" v-else><i class="fas fa-spinner fa-spin" v-if="loadingProduct"></i> Enable</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card" v-else>
            <div class="tbl-sync-list p-2">
                <table>
                    <tbody>
                        <tr>
                            <td style="width: 16%">
                                <button class="btn bg-default btn-lg btn-icon">
                                    <svg
                                        height="25"
                                        viewBox="0 0 576 144">
                                        <use xlink:href='/images/hitpay.svg#hitpay'></use>
                                    </svg>
                                </button>
                            </td>
                            <td align="center" style="width: 5%">
                                <i class="fas fa-3x fa-long-arrow-alt-left color-gray"></i>
                            </td>
                            <td align="right" style="width: 16%">
                                <button class="btn bg-gray btn-lg btn-icon">
                                    <img src="/images/shopify.svg" alt="shopify">
                                </button>
                            </td>
                            <td align="center" style="width: 15%">
                                <p class="mb-0">Status</p>
                                <span class="badge bg-danger text-white">Inactive</span>
                            </td>
                            <td align="right" style="width: 33%">
                                <button class="btn bg-default btn-sm" align="right" @click.prevent="connectEcommerceFlow"><i class="fas fa-spinner fa-spin" v-if="loadingEcommerce"></i> Enable</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script>
    import moment from 'moment';
    export default {
        name: "HotGlue",
        props: {
            hotglue_configs: Object
        },
        data() {
            return {
                loadingEcommerce: false,
                loadingProduct: false,
                isProcessingPeriodicSync: false,
                isProcessingSyncAllHitpayOrders: false,
                isProcessingSaveLocation: false,
                disconnectEcommerceLoading: false,
                disconnectProductLoading: false,
                ecommerce_flow_periodic_sync: false,
                sync_hitpay_product: false,
                sync_integration_id: null,
                sync_all_hitpay_orders: false,
                hotglueLinkedDetails: []
            }
        },
        mounted() {
            HotGlue.mount({
                'api_key': this.hotglue_configs.public_api_key,
                'env_id': this.hotglue_configs.env_id
            });
            this.hotglueLinkedDetails = Business.hotglueIntegration;
            Business.hotglueIntegration.forEach(integration => {
                if (integration.connected == 1 && integration.flow == this.hotglue_configs.ecommerce_flow_id) {
                    this.ecommerce_flow_periodic_sync = true;
                    this.sync_integration_id = integration.id;
                }
                if (integration.periodic_sync == 1) {
                        this.sync_hitpay_product = true;
                }
                if (integration.sync_all_hitpay_orders == 1) {
                        this.sync_all_hitpay_orders = true;
                }
            });
        },

        methods: {
            connectEcommerceFlow() {
                this.loadingEcommerce = true;
                setTimeout(() => this.openHotglueModal('shopify'), 3000);
            },

            connectProductFlow(target) {
                this.loadingProduct = true;
                axios.post(this.getDomain('business/' + Business.id + '/integration/hotglue/target-linked', 'dashboard'),
                    { target: target, flow: this.hotglue_configs.product_flow_id }
                ).then(({data}) => {
                    this.loadingProduct = false;
                    this.hotglueLinkedDetails = data;
                    this.ecommerce_flow_periodic_sync = true;
                });
            },

            productPeriodicSync() {
                if (this.isProcessingPeriodicSync) {
                    return;
                }
                this.isProcessingPeriodicSync = true;
                setTimeout(() => {
                    axios.put(this.getDomain('business/' + Business.id + '/integration/hotglue/product-periodic-sync', 'dashboard'),
                        { id: this.sync_integration_id, periodic_sync: this.sync_hitpay_product }
                    ).then(() => {
                        this.isProcessingPeriodicSync = false;
                    });
                }, 1000);
            },

            syncAllHitPayOrders() {
                if (this.isProcessingSyncAllHitpayOrders) {
                    return;
                }
                this.isProcessingSyncAllHitpayOrders = true;
                setTimeout(() => {
                    axios.put(this.getDomain('business/' + Business.id + '/integration/hotglue/sync-all-hitpay-orders', 'dashboard'),
                        { id: this.sync_integration_id, sync_all_hitpay_orders: this.sync_all_hitpay_orders }
                    ).then(() => {
                        this.isProcessingSyncAllHitpayOrders = false;
                    });
                }, 1000);
            },

            syncNow(hotglue) {
                axios.post(this.getDomain('business/' + Business.id + '/integration/hotglue/sync-now', 'dashboard'),
                    { id: hotglue.id, source: hotglue.source, flow: hotglue.flow }
                ).then(({data}) => {
                    this.hotglueLinkedDetails = data;
                    alert("Sync is in progress. An email will be sent to you once the sync is completed");
                });
            },

            userConnected(source, flow) {
                this.loadingEcommerce = true;
                HotGlue.close();
                axios.post(this.getDomain('business/' + Business.id + '/integration/hotglue/source-connected', 'dashboard'),
                    { source: source.tap, flow: flow }
                ).then(({data}) => {
                    setTimeout(() => {
                        this.loadingEcommerce = false;
                        this.hotglueLinkedDetails = data;
                    }, 1000);
                });
            },

            openHotglueModal(source) {
                HotGlue.setListener({
                    onSourceLinked: (source, flow) => this.userConnected(source, flow)
                });
                HotGlue.link(Business.id, this.hotglue_configs.ecommerce_flow_id, source);
                if (HotGlue.hasMounted()) {
                    setTimeout(() => this.loadingEcommerce = false, 3000);
                }
            },

            disconnect(id, flow) {
                if (flow == 'ecommerce') {
                    this.disconnectEcommerceLoading = true;
                } else {
                    this.disconnectProductLoading = true;
                }
                axios.put(this.getDomain('business/' + Business.id + '/integration/hotglue/source-disconnected', 'dashboard'),
                    { id: id }
                ).then(({data}) => {
                    this.hotglueLinkedDetails = data;
                    if (flow == 'ecommerce') {
                        this.disconnectEcommerceLoading = false;
                    } else {
                        this.disconnectProductLoading = false;
                    }
                    this.ecommerce_flow_periodic_sync = false;
                });
            },

            formatDate(date) {
                return date ? moment(String(date)).format('DD/MM/YYYY h:mm:ss a') : '-';
            },

            saveInventoryLocation(event, integrationId) {
                if (this.isProcessingSaveLocation) {
                    return;
                }
                var location = event.target.options[event.target.selectedIndex].text;
                if (confirm("Set " + location + " as default inventory location?")) {
                    this.isProcessingSaveLocation = true;
                    setTimeout(() => {
                        axios.put(this.getDomain('business/' + Business.id + '/integration/hotglue/inventory-location', 'dashboard'),
                            { id: integrationId, location_id: event.target.value }
                        ).then(({data}) => {
                            this.hotglueLinkedDetails = data;
                            this.isProcessingSaveLocation = false;
                        });
                    }, 1000);
                }
            },

            isSyncCompleted(data, i) {
                var now = moment(new Date());
                if (data.job_in_progress_job_created.length > 0 && data.job_in_progress_job_queued.length === 0) {
                    var job_created_at = moment(data.job_in_progress_job_created[0].created_at);
                    var diff = now.diff(job_created_at, 'seconds');
                    var percent = diff < 60 ? ((diff / 60) * 0.5) * 100 : 50;
                    this.hotglueLinkedDetails[i]['progress_percent'] = percent.toFixed(2);
                    setInterval(() => {
                        if (percent >= 50) {
                            window.location.reload(true);
                        } else {
                            this.$forceUpdate();
                        }
                    }, 30000);
                    return false;
                } else if (data.job_done[0].status === 'SYNCED') {
                    var sync_date = moment(data.job_done[0].sync_date);
                    var diff = now.diff(sync_date, 'seconds');
                    this.hotglueLinkedDetails[i]['progress_percent'] = 100;
                    setInterval(() => {
                        if (diff < 6) {
                            window.location.reload(true);
                        } else {
                            this.$forceUpdate();
                        }
                    }, 5000);
                    return diff < 6 ? false : true;
                } else if (data.job_in_progress_job_queued.length > 0) {
                    this.hotglueLinkedDetails[i]['progress_percent'] = 75;
                    setInterval(() => {
                        window.location.reload(true);
                    }, 30000);
                    return false;
                }
            }
        }
    }
</script>
<style lang="scss">
    .hotglue-w-MuiBreadcrumbs-root a {
        pointer-events: none;
        cursor: default;
    }
    .border-bottom {
        border-bottom: 0.5px solid #979797;
    }
    .disconnect {
        cursor: pointer;
    }
    .btn-icon {
        height: 50px !important;
    }
    .bg-gray {
        background: #d5d5d5;
    }
    .bg-default, .bg-default:hover {
        background: #011B5F;
        color: #fff;
    }
    .color-gray {
        color: #d5d5d5;
    }
    .animatedDots:after {
        content: ' .';
        animation: dots 3s steps(5, end) infinite;
        padding-right: 8px;
        font-size: 16px;
    }

    .tbl-sync-list{
        table{
            width: 100%;
            tbody{
                tr{
                    td{
                        padding: 18px 10px 18px 10px;
                        font-size: 15px;
                        color: #0A1734;
                        border-bottom: 1px solid #E8EAEE;
                        vertical-align:middle;
                    }
                    &:last-child{
                        td{
                            border-bottom: none;
                        }
                    }
                }
            }
        }
    }

    @media (max-width: 992px){
        .tbl-sync-list{
            overflow-x: scroll;
        }
    }

    @keyframes dots {
        0%, 20% {
            color: rgba(0,0,0,0);
            text-shadow:
            .25em 0 0 rgba(0,0,0,0),
            .5em 0 0 rgba(0,0,0,0);
        }
        40% {
            color: white;
            text-shadow:
            .25em 0 0 rgba(0,0,0,0),
            .5em 0 0 rgba(0,0,0,0);
        }
        60% {
            text-shadow:
            .25em 0 0 white,
            .5em 0 0 rgba(0,0,0,0);
        }
        80%, 100% {
            text-shadow:
            .25em 0 0 white,
            .5em 0 0 white;
        }
    }
</style>