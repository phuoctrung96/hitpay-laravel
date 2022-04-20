<template>
  <div class="payment-methods">
    <!-- methods list -->
    <div v-for="method in availableProviders" :key="method.slug">
      <!-- {{ method }} -->
      <div class="card d-flex flex-row align-items-center mt-3 px-3 py-2">
        <div
          v-if="method.color"
          :style="{ 'background-color': method.color }"
          class="
            icon-container
            d-flex
            align-items-center
            justify-content-center
            my-1
            flex-shrink-0
          "
        >
          <img
            :src="method.logo"
            :height="method.height || 20"
            :width="method.width"
          />
        </div>

        <img
          v-else
          class="mt-1"
          :src="method.logo"
          width="72"
          height="72"
          style="margin-left: -4px"
        />

        <div class="d-flex flex-column justify-content-center mx-2 flex-grow-1 ml-3">
          <div class="d-flex pb-2">
          <span class="caption ">{{ method.title }}</span>
          <span class="badge clock ml-3 d-block d-md-none" :class="method.slug == 'dbs_sg' ? 'badge-primary' : 'badge-pink'"
            > {{method.slug == 'dbs_sg' ? 'Restricted Soon' : 'Missing Information' }}<i class="fa fa-clock ml-2"></i
          ></span>
          </div>
          <div
            v-if="method.availableMethods && method.availableMethods.length > 0"
            class="
              d-flex
              flex-column flex-sm-row
              available-methods
              flex-sm-wrap
              align-items-md-center
            "
          >
            <span class="flex-shrink-0 d-none d-sm-block"
              >Available methods:</span
            >

            <div class="d-flex flex-wrap py-2">
              <div
                v-for="(img, index) in method.availableMethods"
                :key="index"
                class="
                  ml-1
                  mr-2
                  d-flex
                  justify-content-center
                  justtify-content-sm-start
                  method
                "
                :class="{
                  full: images[img].fullSize,
                  'full-border': images[img].fullBorder,
                }"
              >
                <img :src="images[img].img" />
              </div>
            </div>
          </div>
          <!-- <span class="text-info"> View Details > </span> -->
        </div>

        <!-- Action -->
        <div v-if="requirePaynow(method)" class="require-paynow">
          Please connect PayNow first
        </div>

        <div
          v-else-if="
            method.requireBusinessVerification &&
            !business_verified &&
            !paymentMethodConnected(method.slug)
          "
          class="d-flex flex-column align-items-center not-available text-right"
        >
          {{ method.title }} is not available for individual sellers. Please
          check your account verification.
        </div>

        <div v-else class="d-flex align-items-center">
          <span class="badge clock mr-4 d-none d-md-block" :class="method.slug == 'dbs_sg' ? 'badge-primary' : 'badge-pink'"
            > {{method.slug == 'dbs_sg' ? 'Restricted Soon' : 'Missing Information' }}<i class="fa fa-clock ml-2"></i
          ></span>
          <a
            v-if="!paymentMethodConnected(method.slug)"
            class="
              view-details
              d-flex
              align-items-center
              justify-content-center
            "
            :class="{
              'view-details': paymentMethodConnected(method.slug),
              connect: !paymentMethodConnected(method.slug),
            }"
            :href="`/business/${business.id}/payment-provider/${method.link}`"
          >
            {{
              paymentMethodConnected(method.slug)
                ? "VIEW DETAILS"
                : "Connect Now"
            }}
          </a>
          <div v-else class="">
            <a
              data-toggle="collapse"
              :href="`#${method.slug}`"
              aria-expanded="false"
              aria-controls="collapseOne"
            >
              <i class="fa fa-angle-up text-muted" style="font-size: 25px"></i>
            </a>
          </div>

          <span v-if="status(method.slug)">
            <span v-html="status(method.slug)"></span>
          </span>
        </div>
      </div>
      <div class="collapse price-info" :id="method.slug">
        <div class="card card-body d-flex text-muted" >
          <hr />
          <div class="row" v-for="item in providersData" :key="item.id">
            <div class="col-sm-5" v-if="item.payment_provider == method.slug">
              <div class="row" >
                <div class="col-5 pb-2">Status</div>
                <div class="col-7 pb-2"><span class="text-success">{{
                      item.payment_provider_status == 1
                        ? "Connected"
                        : "disabled"
                    }}</span></div>
                <div class="col-5 pb-2">Payments</div>
                <div class="col-7 pb-2 font-weight-bold">Enabled</div>
                <div class="col-5 pb-2">Payouts</div>
                <div class="col-7 pb-2 font-weight-bold">Enabled</div>
              </div>
            </div>
            <div class="col-sm-7" v-if="item.payment_provider == method.slug">
              <div class="row">
                <div class="col-5 pb-2">Pricing</div>
                <div class="col-7 pb-2 font-weight-bold">3% + $0.50</div>
                <div class="col-5 pb-2">Payout Schedule</div>
                <div class="col-7 pb-2 font-weight-bold">2 Business Days</div>
                <div class="col-5 pb-2"> Integration</div> 
                 <div class="col-7 pb-2"><span class="w-50"
                      ><span class="badge badge-primary">Woo Commerce</span>
                      <span class="badge badge-primary">Payment Links</span>
                      <span class="badge badge-primary">Invoice</span>
                      <span class="badge badge-primary">POS</span>
                      <span class="badge badge-primary">Online store</span>
                      <span class="badge badge-primary">Wix</span>
                      <span class="badge badge-primary">Shopify</span>
                      <span class="badge badge-primary">Xero</span>
                      <button type="button" class="btn btn-primary mr-2">
                        + Add
                      </button></span
                    ></div>
              </div>
            </div>
          </div>
              <span class="text-primary"> View Details > </span>
           
             </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { merge } from "lodash";

export default {
  name: "PaymentMethods",
  props: {
    business_id: String,
    current_business_user: Object,
    user: Object,
  },
  data() {
    return {
      providersData: [],
      //page: this.tab || 'paynow',
      page: "",
      message: "",
      messageClass: "",
      supportedPaymentProviders: [],
      images: {
        paynow: {
          img: "/icons/payment-providers/paynow.png",
        },
        visa: {
          img: "/icons/payment-brands/visa.png",
        },
        master: {
          img: "/icons/payment-brands/master.png",
        },
        amex: {
          img: "/icons/payment-brands/amex.svg",
          fullSize: true,
        },
        wechat: {
          img: "/icons/payment-methods/weechat-new2.svg",
        },
        alipay: {
          img: "/icons/payment-methods/alipay-new.svg",
        },
        apple: {
          img: "/icons/payment-brands/apple-pay.svg",
          fullSize: true,
        },
        grabpay: {
          img: "/icons/payment-methods/grabpay2.png",
        },
        grab_paylater: {
          img: "/icons/payment-brands/grabpay_paylater.svg",
          fullSize: true,
        },
        unionpay: {
          img: "/icons/payment-brands/unionpay.svg",
          fullSize: true,
        },
        googlepay: {
          img: "/icons/payment-brands/gpay.svg",
          fullBorder: true,
        },
        zip: {
          img: "/icons/payment-brands/zip.svg",
          fullBorder: true,
        },
        shopee: {
          img: "/icons/payment-brands/shopee.svg",
          fullBorder: true,
        },
        fpx: {
          img: "/icons/payment-brands/fpx.png",
        },
        down: {
          img: "/icons/imgpsh_fullsize_anim.png",
        },
      },
      business: Object,
      banks_list: [],
      providers: [],
      disabled_providers: [],
      tab: String,
      business_verified: Boolean,
    };
  },
  created() {},
  computed: {
    availableProviders() {
      return this.supportedPaymentProviders.filter(
        (p) => !this.disabled_providers.includes(p.slug)
      );
    },
    paynow() {
      const provider = this.providersData.find(
        (p) => p.payment_provider === "dbs_sg"
      );

      if (provider) {
        // const code = provider.payment_provider_account.id.split('@');
        const bank = this.banks_list.find(
          (b) => b.swift_code === provider.data.account.swift_code
        );

        return {
          company_uen: provider.data.company.uen,
          company_name: provider.data.company.name,
          bank_account_name: provider.data.account.name,
          bank_name: bank.name,
          bank_swift_code: provider.data.account.swift_code,
          bank_account_no: provider.data.account.number,
        };
      } else {
        return undefined;
      }
    },
    stripe() {
      return this.providersData.find((p) => p.payment_provider === "stripe_sg");
    },
    shopee() {
      return this.providersData.find(
        (p) => p.payment_provider === "shopee_pay"
      );
    },
  },
  methods: {
    prepareComponent() {
      this.getBusiness();
      this.getBanksList();
      this.getPaymentProviders();
    },

    prepareSupportedPaymentProviders() {
      // set stripe to each country, better later on backend
      if (this.business.country === "sg") {
        this.supportedPaymentProviders.push(
          {
            title: "PayNow",
            logo: "/icons/payment-providers/paynow.png",
            slug: "dbs_sg",
            link: "paynow",
            color: "#FFFFFF",
            requireBusinessVerification: false,
            availableMethods: ["paynow"],
          },
          {
            title: "GrabPay",
            logo: "/icons/payment-providers/grabpay.svg",
            slug: "grabpay",
            link: "grabpay",
            requireBusinessVerification: true,
            availableMethods: ["grabpay", "grab_paylater"],
          },
          {
            title: "Shopee Pay",
            logo: "/icons/payment-providers/shopee.png",
            slug: "shopee_pay",
            link: "shopee",
            color: "#EE4D2A",
            availableMethods: ["shopee"],
          },
          {
            title: "Zip",
            logo: "/icons/payment-providers/zip.png",
            slug: "zip",
            link: "zip",
            color: "rgb(65, 23, 96)",
            availableMethods: ["zip"],
          }
          //{ title: 'Hoolah', logo: '/icons/payment-providers/hoolah.png', slug: 'hoolah', link: 'hoolah', color: '#D62E2E', height: 16 }
        );

        let stripeSg = this.providers.find(
          (item) => item.payment_provider === "stripe_sg"
        );
        if (stripeSg && stripeSg.payment_provider_account_type === "custom") {
          this.supportedPaymentProviders.push({
            title: "Cards and Alipay",
            logo: "/icons/payment-providers/credit-cards.png",
            slug: "stripe_sg",
            link: "stripe",
            color: "#FFFFFF",
            height: 50,
            requireBusinessVerification: false,
            availableMethods: [
              "visa",
              "master",
              "amex",
              "alipay",
              "apple",
              "unionpay",
              "googlepay",
            ],
          });
        } else {
          this.supportedPaymentProviders.push({
            title: "Stripe",
            logo: "/icons/payment-providers/stripe.png",
            slug: "stripe_sg",
            link: "stripe",
            color: "#635BFF",
            requireBusinessVerification: false,
            availableMethods: [
              "visa",
              "master",
              "amex",
              "wechat",
              "alipay",
              "apple",
              "unionpay",
              "googlepay",
            ],
          });
        }
      }

      if (this.business.country === "my") {
        this.supportedPaymentProviders.push({
          title: "HitPay Payment Gateway",
          logo: "/icons/payment-providers/hitpay.svg",
          slug: "stripe_my",
          link: "stripe",
          color: "#FFFFFF",
          width: 55,
          requireBusinessVerification: false,
          availableMethods: [
            "visa",
            "master",
            "googlepay",
            "apple",
            "unionpay",
            "fpx",
            "alipay",
            "grabpay",
          ],
        });
      }
    },

    getBusiness() {
      axios
        .get(this.getDomain(`v1/business/${this.business_id}`, "api"), {
          withCredentials: true,
        })
        .then((response) => {
          this.business = response.data;
          this.prepareSupportedPaymentProviders();
        });
    },

    getBanksList() {
      axios
        .get(
          this.getDomain(
            `v1/business/${this.business_id}/payment-providers/banks`,
            "api"
          ),
          {
            withCredentials: true,
          }
        )
        .then((response) => {
          this.banks_list = response.data.banks;
        });
    },

    getPaymentProviders() {
      axios
        .get(
          this.getDomain(
            `v1/business/${this.business_id}/payment-providers`,
            "api"
          ),
          {
            withCredentials: true,
          }
        )
        .then((response) => {
          this.providers = response.data.providers;
          this.providersData = this.providers;
          this.disabled_providers = response.data.disabled_providers;
          this.business_verified = response.data.business_verified;
        });

      axios
        .get(
          this.getDomain(
            `v1/business/${this.business_id}/payment-providers/stripe/payout`,
            "api"
          ),
          {
            withCredentials: true,
          }
        )
        .then((response) => {
          console.log("response.data.providers----->", response.data);
        });
    },

    onPaynowSaved(event) {
      const data = {
        payment_provider_account_id: `${event.bank_swift_code}@${event.bank_account_no}`,
        data: {
          company: {
            uen: event.company_uen,
            name: event.company_name,
          },
          account: {
            name: event.bank_account_name,
          },
        },
      };

      if (!this.business.verified_wit_my_info_sg) {
        window.location.href = this.getDomain(
          "business/" + this.business.id + "/verification",
          "dashboard"
        );
      }

      const provider = this.providersData.find(
        (p) => p.payment_provider === "dbs_sg"
      );

      if (provider) {
        merge(provider, data);
      } else {
        // PayNow created first time
        this.providersData.push({
          payment_provider: "dbs_sg",
          ...data,
        });

        // Hide global alert, since it is in other Vue instance this is the only way
        const el = document.querySelector("#globalAlert");

        if (el) {
          el.style.display = "none";
        }
      }
    },
    onMessage({ text, success }) {
      this.message = text;
      this.messageClass = success ? "alert-success" : "alert-danger";
    },
    paymentMethodConnected(method) {
      return this.providersData.find((p) => p.payment_provider === method);
    },
    removeMethod(method) {
      this.providersData.filter(
        (provider) => provider.payment_provider !== method
      );
    },
    onStripeRemove() {
      if (this.business.country === "sg") {
        this.removeMethod("stripe_sg");
      }

      if (this.business.country === "my") {
        this.removeMethod("stripe_my");
      }

      this.page = "";
    },
    onShopeeRemoved() {
      this.removeMethod("shopee_pay");
      this.page = "";
    },
    onUpdate(data) {
      console.log(data);
    },
    requirePaynow(method) {
      return (
        (method.slug === "shopee_pay" ||
          method.slug === "hoolah" ||
          method.slug === "grabpay") &&
        !Boolean(this.paynow)
      );
    },
    status(slug) {
      const provider = this.paymentMethodConnected(slug);

      if (provider) {
        switch (this.paymentMethodConnected(slug).onboarding_status) {
          case "pending_submission":
          case "pending_verification":
            var default_label =
              '<span class="on-review">Pending Approval</span>';

            if (
              (this.paymentMethodConnected(slug).payment_provider ===
                "stripe_sg" ||
                this.paymentMethodConnected(slug).payment_provider ===
                  "stripe_my") &&
              this.paymentMethodConnected(slug)
                .payment_provider_account_type === "custom"
            ) {
              default_label = this.getStripeStatusLabel(
                this.paymentMethodConnected(slug)
              );
            }

            return default_label;
          case "rejected":
            return '<span class="rejected">Rejected</span>';
          case "success":
            var default_label = "";

            if (
              (this.paymentMethodConnected(slug).payment_provider ===
                "stripe_sg" ||
                this.paymentMethodConnected(slug).payment_provider ===
                  "stripe_my") &&
              this.paymentMethodConnected(slug)
                .payment_provider_account_type === "custom"
            ) {
              default_label = this.getStripeStatusLabel(
                this.paymentMethodConnected(slug)
              );
            }

            return default_label;
          default:
            return "";
        }
      } else {
        return "";
      }
    },

    getStripeStatusLabel(paymentProvider) {
      let payout_enabled = false;
      let charge_enabled = false;

      if (typeof paymentProvider.data == "undefined") {
        return '<span class="on-review">Pending Approval</span>';
      }

      if (typeof paymentProvider.data.account == "undefined") {
        return '<span class="on-review">Pending Approval</span>';
      }

      let data = paymentProvider.data.account;

      if (typeof data.charges_enabled !== "undefined") {
        charge_enabled = data.charges_enabled;
      }

      if (typeof data.payouts_enabled !== "undefined") {
        payout_enabled = data.payouts_enabled;
      }

      let label_charge_enable = charge_enabled
        ? 'Payments: <b class="text-info">Enabled</b>'
        : 'Payments: <b class="text-warning">Disabled</b>';
      let label_payout_enable = payout_enabled
        ? 'Payout: <b class="text-primary">Enabled</b>'
        : 'Payout: <b class="text-warning">Disabled</b>';

      if (charge_enabled && payout_enabled) {
        return "";
      } else {
        return label_charge_enable + " - " + label_payout_enable;
      }
    },
  },

  mounted() {
    this.prepareComponent();
  },
};
</script>

<style lang="scss" scoped>
.payment-methods {
  .caption {
    font-size: 14px;
    font-weight: 500;
  }

  .icon-container {
    width: 63px;
    height: 63px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    border-radius: 6px;
  }

  .view-details,
  button.connect {
    height: 40px;
    width: 142px;
    font-size: 14px;
  }

  .view-details {
    color: #5d9de7;
    background-color: white;
    cursor: pointer;
  }

  .on-review {
    font-size: 14px;
    font-weight: bold;
    color: orange;

    &.rejected {
      color: red;
    }
  }

  .connect {
    color: white;
    background-color: #011b5f;
    border-radius: 5px;
    border: 0;
    font-weight: 500;
  }

  .available-methods {
    span {
      font-size: 14px;
      color: #4a4a4a;
    }

    .method {
      height: 22px;
      border: 1px solid lightgrey;
      overflow: hidden;
      padding: 2px 4px;
      border-radius: 2px;
      margin: 1px 0;

      &.full {
        border: none;
        padding: 0px;

        img {
          height: 100%;
        }
      }

      &.full-border {
        padding: 0px;

        img {
          height: 100%;
        }
      }

      img {
        height: calc(100% - 4px);
      }
    }
  }

  .method-info {
    font-size: 14px;
    color: #4a4a4a;
  }

  .require-paynow {
    width: 130px;
    font-size: 14px;
    text-transform: uppercase;
    text-align: center;
  }

  .not-available {
    width: 250px;
    font-size: 13px;
  }
  [aria-expanded="false"] {
    i {
      transform: rotate(180deg);
    }
  }
  .badge-primary.clock {
    background: #ffe3d0;
    color: #ff7c24;
    font-weight: bold;
    padding: 4px;
  }

  .badge-pink {
    background-color: #f5d2f9;
    color: #d836bf;
  }

  .price-info {
    .list-group {
      flex-direction: row;
      flex-wrap: wrap;
    }

    .badge-primary {
      padding: 9px;
      color: #007bff;
      background-color: #007bff26;
      font-weight: 700;
      margin-bottom: 4px;
    }

    button {
      padding: 2px 14px;
    }
    .text-primary {
      color: #007bff !important;
    }
  }
}
</style>
