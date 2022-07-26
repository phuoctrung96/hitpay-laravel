<style scoped>
</style>

<template>
  <div class="col-md-9 col-lg-8 main-content">
    <div class="card shadow-sm mb-3">
      <div class="card-body p-4">
        <h2 class="text-primary mb-3 title">
          Discount
          <span><i v-if="is_retrieving_data" class="fas fa-spin fa-spinner" :class="{'d-none' : !is_retrieving_data}"></i></span>
        </h2>
        <br/>
        <a class="btn btn-primary" @click="addNewDiscount">
          <i class="fas fa-plus mr-2"></i> Add Discount
        </a>
      </div>

      <template v-if="discounts.length > 0">
        <a class="hoverable" v-for="(discount,index) in discounts" :key="discount.id">
          <div class="card-body bg-light border-top p-4">
            <div class="media">
              <div class="media-body">
                <span v-if="discount.fixed_amount" class="font-weight-bold text-dark float-right">
                  {{ getDisplayFixAmount(discount) }}
                </span>
                <span v-else class="font-weight-bold text-dark float-right">
                  {{ getDisplayPercentageAmount(discount) }}
                </span>

                <p class="font-weight-bold mb-2">{{ discount.name }}</p>

                <p v-if="discount.minimum_cart_amount" class="text-dark small mb-2">
                  <span>Minimum Purchase Amount ({{ discount.applies_to_type_name }}):</span>
                  <span class="text-muted">
                    {{ getMinimumCartAmount(discount) }}
                  </span>
                </p>

                <p v-if="discount.is_promo_banner" class="small text-secondary mb-0">ENABLE ON BANNER</p>
              </div>
            </div>
            <div class="media-bottom">
              <div class="mt-2">
                <a @click="editDiscount(discount)">
                  <i class="fa fa-edit"></i> <span>Edit</span>
                </a>
                <a @click="deleteDiscount(discount)" class="float-right">
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
      discounts: [],
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

      await axios.get(this.getDomain(`v1/business/${this.business.id}/discount`, 'api'), {
        params: this.getRequestParams(),
        withCredentials: true
      })
        .then((response) => {
          this.discounts = response.data.data;
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

    addNewDiscount() {
      window.location.href = this.getDomain('business/' + Business.id + '/discount/create', 'dashboard');
    },

    getDisplayFixAmount(discount) {
      return this.getFormattedAmount(this.business.currency, discount.fixed_amount);
    },

    getDisplayPercentageAmount(discount) {
      let percentage = discount.percentage;

      let value = Math.round(percentage * 100);

      return value + '%';
    },

    getMinimumCartAmount(discount) {
      return this.getFormattedAmount(this.business.currency, discount.minimum_cart_amount);
    },

    editDiscount(discount) {
      window.location.href = this.getDomain('business/' + Business.id + '/discount/' + discount.id + '/edit', 'dashboard');
    },

    async deleteDiscount(discount) {
      if (confirm('Are you sure want to delete discount ' + discount.name + '?')) {
        this.is_retrieving_data = true;

        await axios.delete(this.getDomain('v1/business/' + this.business.id + '/discount/' + discount.id, 'api'),{
            withCredentials: true
        }).then(({data}) => {
            alert('Discount ' + discount.name + ' has been deleted');

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
