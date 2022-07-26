<template>
  <div class="getting_started">
  <div class="dashboard-card d-flex flex-column flex-grow-1">
      <div class="title">
        <h3>Get started with HitPay</h3>
      </div>
      <div class="item-step">
        <div class="item clearfix" v-if="is_setup_payment_method == false">
          <div class="icon-number-circle">1</div>
          <div class="getting-content">
            <div class="getting-top">
              <div class="item-title">
                <p><a data-toggle="collapse" href="#collapse1" role="button" aria-expanded="true" aria-controls="collapse1">Setup payment methods</a></p>
              </div>
              <div class="item-sub-title collapse" id="collapse1" :class="is_setup_payment_method == false ? 'show': ''">
                <p>Hitpay supports wide ranage of payment methods. Setting up multiple payment methods increases your conversion</p>
                <div class="getting-bottom getting-bottom d-flex justify-content-between justify-content-between align-items-center">
                  <a :href="`/business/${business.id}/payment-provider`" class="btn btn-primary">Setup now</a>
                  <a href="https://hitpayapp.com/pricing" class="view-more" target="_blank">View Pricing?</a>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="item clearfix active" v-if="is_setup_payment_method == true">
          <div class="icon-check-circle">
            <img src="\images\ico-checked.svg" class="icon-check">
          </div>
          <div class="getting-content">
           <div class="getting-top">
              <div class="item-title">
                <p>Setup payment methods</p>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="item-step">
        <div class="item clearfix" v-if="is_setup_payment_method == false">
          <div class="icon-number-circle">2</div>
          <div class="getting-content">
            <div class="getting-top">
              <div class="item-title">
                <p><a data-toggle="collapse" href="#collapse2" role="button" aria-expanded="true" aria-controls="collapse2">Finish account verification</a></p>
              </div>
              <div class="item-sub-title collapse" id="collapse2">
                <p>Submit your business information to start accepting payments. It takes less than 3 mins to finish this.</p>
                <div class="getting-bottom getting-bottom d-flex justify-content-between justify-content-between align-items-center">
                  <a :href="`/business/${business.id}/verification`" class="btn btn-primary">Verify now</a>
                  <a :href="verify_support_link" class="view-more" target="_blank">Why do I need to verify?</a>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="item clearfix" v-if="is_setup_payment_method == true && is_finish_account_verification == false && verification_status == 'pending'">
          <div class="icon-number-circle">2</div>
          <div class="getting-content">
            <div class="getting-top">
              <div class="item-title">
                <p>Finish account verification</p>
              </div>
              <div class="item-sub-title">
                <p>Submit your business information to start accpeting payments. It takes less than 3 mins to finish this.</p>
                <div class="getting-bottom getting-bottom d-flex justify-content-between justify-content-between align-items-center">
                  <a :href="`/business/${business.id}/verification`" class="btn btn-primary">Verify now</a>
                  <a href="https://hitpay.zendesk.com/hc/en-us/articles/900006274443-How-to-verify-my-account-using-MyInfo" class="view-more" target="_blank">Why do I need to verify?</a>
                </div>
              </div>
            </div>
          </div>
        </div>
          <div class="item clearfix" v-if="is_setup_payment_method == true && verification_status == 'submitted'">
              <div class="icon-check-circle">
                  <img src="\images\ico-checked.svg" class="icon-check">
              </div>
              <div class="getting-content">
                  <div class="getting-top">
                      <div class="item-title">
                          <p>Account verification submitted</p>
                      </div>
                  </div>
              </div>
          </div>
        <div class="item clearfix active" v-if="is_setup_payment_method == true && is_finish_account_verification == true">
          <div class="icon-check-circle">
            <img src="\images\ico-checked.svg" class="icon-check">
          </div>
          <div class="getting-content content">
            <div class="getting-top">
              <div class="item-title">
                <p>Finish account verification</p>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="item-step">
        <div class="item clearfix" v-if="is_setup_payment_method == true && (is_finish_account_verification == true || verification_status == 'submitted') && is_start_accept_payment == false">
          <div class="icon-number-circle">3</div>
          <div class="getting-content">
            <div class="getting-top">
              <div class="item-title">
                <p>Start accepting payments. How are you looking to accept payments?</p>
              </div>
              <div class="sub-item">
                <div class="all-items">
                    <div v-for="item in accept_payments">
                      <label class="label-checkbox" v-if="item.id == 1">
                        <input type="radio" :value="item.id" v-model="accept_payment_type" @click="click_plugin" :disabled="is_processing"> {{ item.name }}
                        <span class="checkmark"></span>
                      </label>
                      <label class="label-checkbox" v-if="item.id != 1 && accept_payment_type != 1">
                        <input type="radio" :value="item.id" v-model="accept_payment_type" :disabled="is_processing"> {{ item.name }}
                        <span class="checkmark"></span>
                      </label>
                    </div>
                </div>
                <div class="all-plugins" v-if="accept_payment_type == 1">
                  <div v-for="plugin in plugins_platform" class="item-plugin">
                      <label class="label-checkbox">
                        <input type="radio" :value="plugin.id" v-model="plugins_type" :disabled="is_processing"> {{ plugin.name }} <img :src="plugin.img_url" class="plugin-icon"/>
                        <span class="checkmark"></span>
                      </label>
                  </div>
                  <div class="view-more">
                    <a :href="plugins_platform_url" class="btn btn-primary mt-2" target="_blank">View guide</a>
                  </div>
                </div>
              </div>
            </div>
            <div class="getting-bottom" v-if="accept_payment_type != 1">
              <a :href="accept_payments_url" class="btn btn-primary">Start now</a>
            </div>
          </div>
        </div>
        <div class="item clearfix active" v-if="is_start_accept_payment == true">
          <div class="icon-check-circle">
              <img src="\images\ico-checked.svg" class="icon-check">
            </div>
          <div class="getting-content content">
            <div class="getting-top">
              <div class="item-title">
                <p>Start accepting payments</p>
              </div>
            </div>
          </div>
        </div>
      </div>
  </div>
      <!-- SignPass panel for SG -->
      <VerificationWarning
          v-if="country_code == 'sg'"
          v-model="is_show_modal_verification"
          :businessId="business.id"
          :business="business"
      />
  </div>
</template>

<script>
import VerificationWarning from './VerificationWarning'

const lastCheckItem = 'verified_wit_my_info_sg_warning'

export default {
  name: 'GettingStarted',
  components: {
      VerificationWarning,
  },
  props: {
    country_code: String,
    payment_count: Boolean,
    is_show_modal_verification: Boolean,
    is_verification_verified: Boolean,
    verification_status: String,
  },
  watch: {
    'plugins_type': {
        handler(plugins_type) {
            this.is_processing = true;
            if(this.plugins_type != 0){
              let plugin = this.plugins_platform.find(x => x.id === plugins_type);
              this.plugins_platform_url = plugin.url;
            }else{
              this.plugins_platform_url = "https://www.hitpayapp.com/e-commerce-plugins";
            }

            this.is_processing = false;
        },
        deep: true,
    },
    'accept_payment_type': {
      handler(accept_payment_type) {
            this.is_processing = true;
            if(this.accept_payment_type !== 0 && this.accept_payment_type !== 1){
              let link = this.accept_payments.find(x => x.id === this.accept_payment_type);
              this.accept_payments_url = link.url;
            }else{
              this.accept_payments_url = "";
            }

            this.is_processing = false;
        },
        deep: true,
    }
  },
  data () {
     return {
       business: window.Business,
       is_processing: false,
       is_setup_payment_method: true,
       is_finish_account_verification: false,
       is_start_accept_payment: false,
       accept_payment_type: 0,
       plugins_type: 0,
       plugins_platform_url: "https://www.hitpayapp.com/e-commerce-plugins",
       accept_payments_url: "",
       accept_payments: [
         {id: 1, name:"Plugins for E-Commerce Platforms (Shopify, WooCommerce etc)", url:"https://www.hitpayapp.com/e-commerce-plugins"},
         {id: 2, name:"APIs for custom integration", url: window.Business.id+"/apikey"},
         {id: 3, name:"Build online store with HitPay", url: window.Business.id+"/dashboard"},
         {id: 4, name:"Payment links", url: window.Business.id+"/payment-links"},
         {id: 5, name:"Online Invoicing", url: window.Business.id+"/invoice"},
         {id: 6, name:"Recurring Billing Invoicing", url: window.Business.id+"/recurring-plan"},
         {id: 7, name:"Point of Sale and credit card terminal", url:window.Business.id+"/point-of-sale"}
       ],
       plugins_platform: [
         {id: 1, name:"Shopify", img_url: "/plugins/ico-plugin-01.svg", url: "https://hitpay.zendesk.com/hc/en-us/articles/900000685746-Add-PayNow-to-your-Shopify-E-Commerce-Store"},
         {id: 2, name:"Woo commerce", img_url: "/plugins/ico-plugin-02.svg", url: "https://hitpay.zendesk.com/hc/en-us/articles/900000771503-HitPay-Singapore-Payment-Gateway-PayNow-QR-Payment-Gateway-Singapore-Add-HitPay-to-your-WooCommerce-Store"},
         {id: 3, name:"Prestashop", img_url: "/plugins/ico-plugin-03.svg", url: "https://hitpay.zendesk.com/hc/en-us/articles/900001912306-HitPay-Prestashop-Payment-Gateway-Singapore-Add-HitPay-to-Prestashop-Online-Stores"},
         {id: 4, name:"Ecwid", img_url: "/plugins/ico-plugin-04.svg", url: "https://hitpay.zendesk.com/hc/en-us/articles/900006056083-HitPay-Ecwid-Payment-Gateway-Singapore-Add-HitPay-to-Ecwid-Online-Stores"},
         {id: 5, name:"Wix", img_url: "/plugins/ico-plugin-05.svg", url: "https://hitpay.zendesk.com/hc/en-us/articles/900001943683-HitPay-Wix-Payment-Gateway-Singapore-Wix-PayNow-QR-Payment-Gateway-How-to-add-HitPay-to-my-Wix-Site"},
         {id: 6, name:"Opencart", img_url: "/plugins/ico-plugin-06.svg", url: "https://hitpay.zendesk.com/hc/en-us/articles/900003748706-HitPay-OpenCart-Payment-Gateway-Singapore-Add-HitPay-to-OpenCart"},
         {id: 7, name:"Shopcada", img_url: "/plugins/ico-plugin-07.svg", url: "https://hitpay.zendesk.com/hc/en-us/articles/900004632006-HitPay-Shopcada-Payment-Gateway-Singapore-How-to-enable-HitPay-in-Shopcada-"},
         {id: 8, name:"Easystore", img_url: "/plugins/ico-plugin-09.svg", url: "https://hitpay.zendesk.com/hc/en-us/articles/900004982143-HitPay-EasyStore-Payment-Gateway-Singapore-Add-HitPay-to-EasyStore-PayNow-QR-EasyStore-Singapore"},
         {id: 9, name:"Magento", img_url: "/plugins/ico-plugin-08.svg", url: "https://hitpay.zendesk.com/hc/en-us/articles/900002303026-HitPay-Magento-Payment-Gateway-Singapore-Add-HitPay-to-Magento-Online-Stores"},
       ],
       verify_support_link: "https://hitpay.zendesk.com/hc/en-us/articles/900006274443-How-to-verify-my-account-using-MyInfo",
     }
  },
  mounted() {
    this.accept_payment_type = 0;
    this.plugins_type = 0;
    this.is_setup_payment_method = this.payment_count;
    this.is_finish_account_verification = this.is_verification_verified;

    if (this.business.country !== 'sg') {
        this.verify_support_link = 'https://hitpay.zendesk.com/hc/en-us/articles/900006274443-How-to-verify-my-account-using-MyInfo';
    }
  },
  methods: {
    click_plugin() {
      if(this.accept_payment_type == 1){
        this.accept_payment_type = 0;
        this.plugins_type = 0;
      }
    }
  },
}
</script>

<style lang="scss">
  .getting_started {
    max-width: 560px;
    margin: 0 auto;
    height: auto;
    font-size: 14px;
    .title {
      padding: 15px 0px 30px;
    }
    .item-step{
      width: 100%;
      float: left;
      border-top: 1px solid #c9c9c9;
      padding: 30px 0px 30px 0px;
      .icon-check-circle{
        border-radius: 50%;
        border: 2px solid #7ED321;
        width: 24px;
        height: 24px;
        text-align: center;
        float: left;
        position: relative;
        top: -2px;
        .icon-check {
          width: 12px;
          height: auto;
          text-align: center;
          position: relative;
          top: -2px;
        }
      }
      .icon-number-circle{
        width: 24px;
        height: 24px;
        border-radius: 50%;
        border: 2px solid #6c757d;
        text-align: center;
        float: left;
        font-size: 14px;
        position: relative;
        top: -2px;
      }
      .getting-content{
        float: left;
        width: 90%;
        margin: 0px 0px 0px 12px;
      }
      .item{
        &.active{
          .getting-top{
            .item-title{
              color: #9B9B9B;
            }
          }
        }
      }
    }
    .getting-top{
      .item-title{
        color: #000;
        p{
          margin: 0px 0px 5px;
        }
      }
      .item-sub-title{
        color: #4A4A4A;
        p{
          margin: 0;
        }
      }
      .item-plugin{
        margin: 0px 0px 0px 20px;
      }
    }
    .getting-bottom{
      width:100%;
      padding: 20px 0px 0px;
      .view-more{
        text-decoration: underline;
        font-size: 14px;
        color: #4A4A4A;
      }
    }
    .item{
      .label-checkbox {
        position: relative;
        padding: 0px 0px 0px 32px;
        margin: 0px 0px 15px;
        cursor: pointer;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
        input {
          position: absolute;
          opacity: 0;
          cursor: pointer;
          height: 0;
          width: 0;
          &:checked ~ .checkmark {
            border: 2px solid #7ED321;
            background: url('~/images/ico-checked.svg') no-repeat center 4px;
            background-size: 12px 9px;
          }
          &:checked ~ .checkmark:after {
              display: none;
          }
        }
        .checkmark {
          position: absolute;
          top: 50%;
          left: 0;
          height: 20px;
          width: 20px;
          border: 1px solid #D4D6DD;
          border-radius: 50%;
          margin-top: -9px;
          &:after {
            content: "";
            position: absolute;
            display: none;
            top: 5px;
            left: 5px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
          }
        }
      }
      .sub-item{
        padding: 12px 0px 0px;
        .all-plugins{
          padding: 2px 0px 0px 0px;
          .item-plugin{
            .plugin-icon{
              width: auto;
              height: 25px;
              margin: 0px 0px 0px 7px;
              position: relative;
            }
            &:nth-child(1){
              .plugin-icon{
                top: -2px;
              }
            }
            &:nth-child(5){
              .plugin-icon{
                height: 20px;
              }
            }
            @media (max-width: 767px) {
              .plugin-icon{
                height: 20px;
              }
              &:nth-child(5){
                .plugin-icon{
                  height: 16px;
                }
              }
            }
          }
          .label-checkbox {
            .checkmark {
              margin-top: -12px;
            }
          }
        }
      }
    }
  }
</style>
