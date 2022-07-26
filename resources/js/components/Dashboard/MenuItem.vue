<template>
  <div class="is-menu-item">
    <a
      class="menu-item clearfix"
      :class="{ current }"
      :href="this.item.path"
      @click="onClick">
      <div
        class="icon-current"
        :class="{ current }">

        <svg
          v-if="item.icon"
          width="16"
          height="16"
          viewBox="0 0 20 20">
          <use :xlink:href="`/icons/left-menu/${item.icon}#hitpay`"></use>
        </svg>
      </div>
      <div class="text-menu">{{ item.title }} <span v-if="item.child_title" :id="item.id"> {{ item.child_title }}</span></div>
    </a>
    <a v-if="item.title == 'Online Shop'" :href="item.shop_url" class="icon-redirect" target="_blank"><img src="/images/ico-show-password.svg"></a>
    <a v-if="item.title == 'Online Shop' && order_pending_count > 0 && !expanded" class="order-pending" :id="item.id" :href="order_url">
        <span>{{ order_pending_count > 99 ? "99+" : order_pending_count }}</span>
    </a>
    <a v-if="item.title == 'Orders' && order_pending_count > 0" class="order-pending" :href="order_url"><span>{{ order_pending_count > 99 ? "99+" : order_pending_count }}</span></a>
  </div>
</template>

<script>
export default {
  name: 'MenuItem',
  props: {
    item: Object,
    forceSelect: {
      type: Boolean,
      default: false
    },
    expanded: false
  },
  data() {
    return {
      order_pending_count: "",
      order_url: this.getDomain(`/business/${this.item.business_id}/order?status=requires_business_action`, 'dashboard')
    }
  },
  mounted() {
    if(this.item.title == 'Online Shop' || this.item.title == 'Orders') {
      this.getOrderPending();
    }
  },
  computed: {
    current () {
      return this.forceSelect ||
        (this.item.path && window.location.href.endsWith(this.item.path)) ||
        (this.item.path && window.location.href.includes(this.item.path + "/")) ||
        (this.item.additionalPaths && this.item.additionalPaths.some(path => window.location.href.endsWith(path)))
    },
    order() {

    }
  },
  methods: {
    onClick (event) {
      if (this.item.action) {
        event.preventDefault()
        this.item.action()
      } else {
        if (!this.item.path) {
          this.$emit('click');
        }
        this.captureEventPostHog();
      }
    },
    getOrderPending() {
      axios.get(this.getDomain(`v1/business/${this.item.business_id}/order?statuses=pending`, 'api'), {
          withCredentials: true
      }).then(response => {

        this.order_pending_count = response.data.data.length;
      });
    },
    captureEventPostHog() {
      if(this.item.title == 'Dashboard')
        this.postHogOnlyCaptureData('Click Dashboard', '');
      if(this.item.title == 'Products')
        this.postHogOnlyCaptureData('Click Products', '');
      if(this.item.title == 'Product Categories')
        this.postHogOnlyCaptureData('Click Product Categories', '');
      if(this.item.title == 'Orders')
        this.postHogOnlyCaptureData('Click Orders', '');
      if(this.item.title == 'Discount')
        this.postHogOnlyCaptureData('Click Discount', '');
      if(this.item.title == 'Coupons')
        this.postHogOnlyCaptureData('Click Coupons', '');
      if(this.item.title == 'Shipping & Pickup')
        this.postHogOnlyCaptureData('Click Shipping & Pickup', '');
      if(this.item.title == 'Store Settings')
        this.postHogOnlyCaptureData('Click Store Settings', '');
      if(this.item.title == 'Inventory Sync')
        this.postHogOnlyCaptureData('Click Inventory Sync', '');
    }
  }
}
</script>

<style lang="scss" scoped>
.is-menu-item{
  position: relative;
  .icon-redirect{
    position: absolute;
    right: 62px;
    top: 12px;
    line-height: 0;
    img{
      width: 17px;
      height: auto;
    }
  }
  .order-pending{
    min-width: 22px;
    height: 22px;
    border-radius: 22px;
    position: absolute;
    right: 21px;
    top: 8px;
    background: #ed3047;
    text-align: center;
    span{
      color: #FFF;
      font-size: 12px;
      line-height: 1px;
      font-weight: 600;
      position: relative;
      top: -2px;
      padding: 0px 3px;
    }
  }
}
.hidden{
  display: none !important;
}
.hidden-try-me {
  display: none !important;
}
.menu-item {
  font-size: 15px;
  cursor: pointer;
  color: white;
  display: block;
  padding: 8px 15px 8px 15px;
  width: 100%;
  border-radius: 6px;
  text-decoration: none;
  &.current, &:hover {
    background: rgba(255, 255, 255, 0.15);
    color: #FFF;
  }
  .icon-current {
    height: 100%;
    width: 27px;
    float: left;
    &.current {

    }
  }
  .text-menu{
    overflow: hidden;
  }

  span{
      background: #d836bf;
      font-size: 9px;
      text-transform: uppercase;
      display: inline-block;
      padding: 3px 5px 3px 5px;
      font-weight: 600;
      margin: 0px 0px 0px 6px;
      border-radius: 5px;
    }
}
</style>
