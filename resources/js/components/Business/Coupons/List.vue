<style scoped>
</style>

<template>
  <div class="col-md-9 col-lg-8 main-content">
    <div class="card shadow-sm mb-3">
      <div class="card-body p-4">
        <h2 class="text-primary mb-3 title">
          Coupon
          <span><i v-if="is_retrieving_data" class="fas fa-spin fa-spinner" :class="{'d-none' : !is_retrieving_data}"></i></span>
        </h2>
        <br/>
        <a class="btn btn-primary" @click="addNew">
          <i class="fas fa-plus mr-2"></i> Add Coupon
        </a>
      </div>

      <template v-if="coupons.length > 0">
        <a class="hoverable" v-for="(coupon,index) in coupons" :key="coupon.id">
          <div class="card-body bg-light border-top p-4">
            <div class="media">
              <div class="media-body">
                <span v-if="coupon.fixed_amount" class="font-weight-bold text-dark float-right">
                  {{ getDisplayFixAmount(coupon) }}
                </span>
                <span v-else class="font-weight-bold text-dark float-right">
                  {{ getDisplayPercentageAmount(coupon) }}
                </span>

                <p class="font-weight-bold mb-2">{{ coupon.name }}</p>

                <p v-if="coupon.minimum_cart_amount" class="text-dark small mb-2">
                  <span>Minimum Purchase Amount ({{ coupon.applies_to_type_name }}):</span>
                  <span class="text-muted">
                    {{ getMinimumCartAmount(coupon) }}
                  </span>
                </p>

                <p v-if="coupon.is_promo_banner" class="small text-secondary mb-0">ENABLE ON BANNER</p>

                <p v-if="coupon.coupons_left" class="small text-secondary mb-0">Coupons left to use: {{ coupon.coupons_left }}</p>
              </div>
            </div>
            <div class="media-bottom">
              <div class="mt-2">
                <a @click="editCoupon(coupon)">
                  <i class="fa fa-edit"></i> <span>Edit</span>
                </a>
                <a @click="deleteCoupon(coupon)" class="float-right">
                  <i class="fa fa-trash"></i> <span>Delete</span>
                </a>
              </div>
            </div>
          </div>
        </a>
      </template>

      <div v-else class="card-body bg-light border-top p-4">
        <div class="text-center text-muted py-4">
          <p><i class="fa fas fa-percent fa-4x"></i></p>
          <p class="small mb-0">- No discount found -</p>
        </div>
      </div>

      <b-pagination
        v-model="page"
        :total-rows="total"
        :per-page="pageSize"
        @change="handlePageChange"
      />
    </div>
  </div>
</template>

<script>
import CurrencyNumber from "../../../mixins/CurrencyNumber";

export default {
  mixins: [
    CurrencyNumber
  ],

  components: {
  },

  data() {
    return {
      business: [],
      coupons: [],
      is_loading: false,
      is_retrieving_data: false,
      page: 1,
      total: 0,
      pageSize: 5
    };
  },

  mounted() {
    this.business = Business;

    this.retrieveItems();
  },
  methods: {
    getRequestParams() {
      let params = {};

      if (this.keywords) params["keywords"] = this.keywords;
      if (this.page) params["page"] = this.page;
      if (this.pageSize) params["perPage"] = this.pageSize;

      return params;
    },

    async retrieveItems() {
      this.is_retrieving_data = true;

      await axios.get(this.getDomain(`v1/business/${this.business.id}/coupon`, 'api'), {
        params: this.getRequestParams(),
        withCredentials: true
      })
        .then((response) => {
          this.coupons = response.data.data;
          this.total = response.data.meta.total;
          this.is_loading = false;
          this.is_retrieving_data = false;
        })
        .catch((e) => {
          this.is_loading = false;
          this.is_retrieving_data = false;
          console.log(e);
        });
    },

    handlePageChange(value) {
      this.page = value;
      this.retrieveItems();
    },

    addNew() {
      window.location.href = this.getDomain('business/' + Business.id + '/coupon/create', 'dashboard');
    },

    getDisplayFixAmount(coupon) {
      return this.getFormattedAmount(this.business.currency, coupon.fixed_amount);
    },

    getDisplayPercentageAmount(coupon) {
      let percentage = coupon.percentage;

      let value = Math.round(percentage * 100);

      return value + '%';
    },

    getMinimumCartAmount(coupon) {
      return this.getFormattedAmount(this.business.currency, coupon.minimum_cart_amount);
    },

    editCoupon(coupon) {
      window.location.href = this.getDomain('business/' + Business.id + '/coupon/' + coupon.id + '/edit', 'dashboard');
    },

    async deleteCoupon(coupon) {
      if (confirm('Are you sure want to delete coupon ' + coupon.name + '?')) {
        this.is_retrieving_data = true;

        await axios.delete(this.getDomain('v1/business/' + this.business.id + '/coupon/' + coupon.id, 'api'),{
            withCredentials: true
        }).then(({data}) => {
            alert('Coupon ' + coupon.name + ' has been deleted');

            this.is_retrieving_data = false;

            this.retrieveItems();
        });
      }
    }
  },
  computed: {

  }
}
</script>
