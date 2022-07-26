<style scoped>
.status-checkbox {
    position: absolute;
    top: 5px;
    left: 5px;
}

.action-panel {
    padding-left: 6px;
}
</style>
<template>
    <div class="col-md-9 col-lg-8 main-content">
        <div class="form-group">
            <div class="input-group input-group-lg">
                <input v-model="keywords" class="form-control border-0 shadow-sm"
                       placeholder="Search By Customer Name / Amount / Remarks / Order ID / Date of Transaction" title="Search Order"
                       name="keywords">
                <div class="input-group-append">
                    <button class="btn btn-primary shadow-sm" @click="doSearch"><i class="fas fa-search"></i></button>
                </div>
            </div>
            <span class="small text-muted">Separate keywords by space, maximum 3 keywords will be processed.</span>
        </div>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-4">
                <h2 class="text-primary mb-0 title">{{ statusUpper }} Orders</h2>
            </div>
            <div class="action-panel row d-flex justify-content-between mr-0 ml-0">
                <div class="col-md-3 mb-2">
                    <select v-model="statusForm" class="form-control form-control-sm" @change="getOrdersWithFilters()">
                        <option v-for="(value,name) in statuses" :value="value" :key="value">
                            {{ name }}
                        </option>
                    </select>
                </div>
                <div class="col-md-6 d-flex justify-content-end mb-2">
                    <div class="pr-2">
                        <datepicker placeholder="From" format="dd-MM-yyyy" v-model="filterDate.dateFrom"
                                    @input="getOrdersWithFilters()"></datepicker>
                    </div>
                    <div class="">
                        <datepicker placeholder="To" format="dd-MM-yyyy" v-model="filterDate.dateTo"
                                    @input="getOrdersWithFilters()"></datepicker>
                    </div>
                </div>
            </div>
            <div v-if="statusForm === 'requires_business_action' && orders.length > 0"
                 class="card-body border-top py-2 action-panel">
                <input type="checkbox" class="all-status-checkbox mr-3" v-model="checkedAll" @click="checkAll()">
                <button class="btn btn-success btn-sm" @click="markAsCompleted">Mark as shipped/picked up</button>
            </div>
            <template v-if="orders.length > 0">
                <a class="hoverable" v-for="(order,index) in orders" :key="order.id" :href="orderLink(order.id)">
                    <div class="card-body bg-light border-top text-dark p-4 position-relative">
                        <input v-if="statusForm === 'requires_business_action'" type="checkbox" class="status-checkbox"
                               v-model="order.checked">
                        <div class="media">
                            <img src="/hitpay/images/product.jpg"
                                 class="d-none d-phone-block listing rounded border mr-3"
                                 alt="order">
                            <div class="media-body align-self-center">
                            <span
                                class="font-weight-bold text-dark float-right">{{ order.currency.toUpperCase() }} {{ order.amount | currency }}</span>
                                <p class="font-weight-bold mb-2">
                                    {{ order.customer.name ? order.customer.name : 'No Name' }}</p>
                                <p class="text-dark small mb-0">Products<br><span class="text-muted">
                                <span v-for="(product,index) in order.products">
                                    {{ product.name }} x {{ product.quantity }}
                                    <span v-if="index != Object.keys(order.products).length - 1">, </span>
                                </span>
                            </span></p>
                                <p class="text-dark small mb-2">Order ID: {{ order.id }}</p>
                                <template v-if="order.status == 'completed'">
                                    <template v-if="order.customer_pickup">
                                        <p class="text-dark small mb-2">Picked up
                                            at {{ order.closed_at }}</p>
                                        <span class="small font-weight-bold text-success">Completed - Picked Up!</span>
                                    </template>
                                    <template v-else>
                                        <p class="text-dark small mb-2">Shipped
                                            at {{ order.closed_at }}</p>
                                        <span class="small font-weight-bold text-success">Completed - Shipped!</span>
                                    </template>
                                </template>
                                <template v-else-if="order.status == 'requires_business_action'">
                                    <template v-if="order.customer_pickup">
                                        <span class="badge badge-warning">Pending - Pickup</span>
                                    </template>
                                    <template v-else>
                                        <p class="text-dark small mb-2">Address<br><span
                                            class="text-muted">{{
                                                order.customer.address.city + ', ' + order.customer.address.street
                                            }}</span>
                                        </p>
                                        <span class="badge badge-warning">Pending - Shipping</span>
                                    </template>
                                </template>
                                <template v-else-if="order.status == 'requires_payment_method'">
                                    <span class="badge badge-info">Payment In Progress</span>
                                </template>
                                <template v-else-if="order.status == 'requires_customer_action'">
                                    <span class="badge badge-info">Waiting For Customer</span>
                                </template>
                                <template v-else-if="order.status == 'canceled'">
                                    <span class="badge badge-danger">Canceled</span>
                                </template>
                                <template v-else-if="order.status == 'expired'">
                                    <span class="badge badge-danger">Canceled</span>
                                </template>
                                <template v-if="order.charges && order.charges.length > 0">
                                    <p class="text-dark small mb-2">Charge ID: {{ order.charges.slice(-1)[0].id }}
                                        <a href="#" style="text-decoration: underline" @click.prevent="copyCharge($event, order.charges.slice(-1)[0].id)">Copy</a></p>
                                    <input type="hidden" :id="'charge'+order.charges.slice(-1)[0].id"
                                           :value="order.charges.slice(-1)[0].id">
                                </template>
                            </div>
                        </div>
                    </div>
                </a>
            </template>
            <div v-else class="text-center text-muted py-4">
                <p><i class="fa far fa-list-alt fa-4x"></i></p>
                <p class="small mb-0">- No order found -</p>
            </div>
            <b-pagination
              v-model="page"
              :total-rows="total"
              :per-page="pageSize"
              @change="handlePageChange"
            />
        </div>

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
                            <p v-if="is_loading"><i class="fas fa-spinner fa-spin"></i> Please wait for order status to be updated</p>
                            <p v-else>Orders have been successfully marked as completed</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Datepicker from 'vuejs-datepicker';

export default {
    components: {
        Datepicker
    },

    data() {
        return {
            business: [],
            orders: [],
            status: 'All',
            statusForm: 'all',
            statuses: [],
            is_loading: false,
            checkedAll: false,
            filterDate: {
                dateFrom: '',
                dateTo: '',
            },
            keywords: '',
            page: 1,
            total: 0,
            pageSize: 5
        };
    },

    mounted() {
        this.business = Business;
        this.statuses = Statuses;

        let urlParams = new URLSearchParams(window.location.search);

        let statusQuery = urlParams.get('status');

        if (statusQuery) {
            this.statusForm = statusQuery;
        }

        this.retrieveItems();
    },
    methods: {
        getRequestParams() {
          let params = {
            'with': 'products,charges'
          };

          if (this.keywords) params["keywords"] = this.keywords;
          if (this.page) params["page"] = this.page;
          if (this.pageSize) params["perPage"] = this.pageSize;
          if (this.filterDate.dateFrom) params['dateFrom'] = getFormattedDate(this.filterDate.dateFrom);
          if (this.filterDate.dateTo) params['dateTo'] = getFormattedDate(this.filterDate.dateTo);
          if (this.statusForm === 'all') {
            params['statuses'] = _.values(this.statuses).filter((value, index) => 'all' !== value).join(',')
          } else {
            params['statuses'] = this.statusForm;
          }

          return params;
        },
        async retrieveItems() {
          this.is_loading = true;

          await axios.get(this.getDomain(`v1/business/${this.business.id}/order`, 'api'), {
            params: this.getRequestParams(),
            withCredentials: true
          })
            .then((response) => {
              this.orders = response.data.data;
              this.total = response.data.meta.total;
              this.is_loading = false;
            })
            .catch((e) => {
              this.is_loading = false;
              console.log(e);
            });
        },
        handlePageChange(value) {
          this.page = value;
          this.retrieveItems();
        },
        orderLink(id) {
            return '/business/' + this.business.id + '/order/' + id;
        },
        checkAll() {
            _.each(this.orders, (order) => {
                order.checked = !this.checkedAll;
            });
        },
        async markAsCompleted() {
            let selectedOrders = this.orders.filter((order, index) => true === order.checked);

            this.is_loading = true;

            $('#confirmationModal').modal();

            let submissionData = {
                'orders': selectedOrders,
                'status': this.statusForm
            };

            await axios.post(this.getDomain('business/' + this.business.id + '/order/update', 'dashboard'), submissionData).then(({data}) => {
                $('#confirmationModal').modal('hide');
                this.is_loading = false;
                // refresh items
                this.retrieveItems();

                $('#confirmationModal').modal();
            });
        },
        async getOrdersWithFilters() {
            if (this.filterDate.dateFrom > this.filterDate.dateTo && this.filterDate.dateTo !== '') {
                alert('Oops, date from is greater than date to.');
                setTimeout(() => { this.filterDate.dateTo = ''}, 5);
                return;
            }

            this.page = 1;
            this.retrieveItems();
        },
        doSearch() {
            this.page = 1;
            this.retrieveItems();
        },

        copyCharge($event, chargeId) {
            let charge = document.querySelector('#charge' + chargeId);
            charge.setAttribute('type', 'text');
            charge.select();

            try {
                navigator.clipboard.writeText(chargeId);

                if($event.path)
                    $event.path[0].innerHTML = "Copied";
                else
                    $event.composedPath()[0].innerHTML = "Copied";

                setTimeout(() => {
                    $event.path[0].innerHTML = "Copy";
                }, 5000);
            } catch (err) {
                alert('Oops, unable to copy');
            }

            /* unselect the range */
            charge.setAttribute('type', 'hidden');
            window.getSelection().removeAllRanges()

        },
    },
    computed: {
        statusUpper() {
            return this.status.charAt(0).toUpperCase() + this.status.slice(1);
        }
    }
}

function getFormattedDate(date)
{
    if (date != '') {
        let year = date.getFullYear();
        let month = (1 + date.getMonth()).toString().padStart(2, '0');
        let day = date.getDate().toString().padStart(2, '0');

        return day + '-' + month + '-' + year;
    }
    return '';
}
</script>
