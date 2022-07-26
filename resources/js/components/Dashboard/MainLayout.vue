<template>
  <div>
    <LeftSideMenu
      :user="user"
      :business="business"/>

    <div class="main-layout-content d-flex flex-column">
        <div v-if="alert_text && type === 'no_payment_provider'" id="alertModal" class="modal" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-body text-center">
                        <h5>{{ alert_text}}</h5>
                        <a :href="alert_link" class="btn btn-primary">{{ alert_link_text }}</a>
                    </div>
                </div>
            </div>
        </div>
        <div
            v-else-if="alert_text"
            id="globalAlert"
            class="alert alert-danger border-top-0 border-left-0 border-right-0 rounded-0 mb-0">
            <div class="container-fluid text-center">
                <p class="small mb-0">{{ alert_text}}
                    <a :href="alert_link">{{ alert_link_text }}</a>
                </p>
            </div>
        </div>

      <div class="help-line d-flex justify-content-between align-items-center py-3 px-4 main-top-bar">
        <span
          v-if="title"
          class="page-title mb-0">
          {{ title }}
        </span>

        <slot
          v-else
          name="test"></slot>

        <div class="d-flex align-items-center">
            <a data-nolt="button" :href="nolt_link" class="mr-3 menu-items-desktop">Feedback</a>
            <div class="announcekit menu-items-desktop">
            <a href="#" class="ak-trigger">What's New <AnnounceKit
                :user="{id: business.id, email: business.email, name: business.name}"
                catchClick=".ak-trigger"
                widget="https://announcekit.co/widgets/v2/5LtzW" />
          </a>
          </div>
          <div class="dropdown ml-4">
            <div
              data-toggle="dropdown"
              class="d-flex align-items-center">
              <span role="button">
                HELP?
              </span>
            </div>

            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink">
              <a class="dropdown-item" href="https://hitpay.zendesk.com" target="_blank">User Guide</a>
                <a href="#" class="ak-trigger menu-items-mobile dropdown-item d-none">What's New <AnnounceKit
                    :user="{id: business.id, email: business.email, name: business.name}"
                    catchClick=".ak-trigger"
                    widget="https://announcekit.co/widgets/v2/5LtzW" />
                </a>
                <a data-nolt="button" :href="nolt_link" class="mr-3 menu-items-mobile dropdown-item d-none">Feedback</a>
            </div>
          </div>

          <div class="dropdown ml-4">
            <img
              src="/icons/acc_icon.svg"
              data-toggle="dropdown"
              role="button"
              aria-haspopup="true" aria-expanded="false"
              class="dropdown-toggle"/>

            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink">
              <span class="dropdown-header">{{ business.name }}</span>
              <div class="dropdown-divider"/>

              <template v-if="isAdmin">
                <a class="dropdown-item" :href="getDomain('', 'admin')">Admin Dashboard</a>
                <div class="dropdown-divider"/>
              </template>

              <a class="dropdown-item" href="/user/profile">Account</a>
              <a class="dropdown-item" href="/user/security">Security</a>
              <a class="dropdown-item logout-item" @click="doLogout()">Log Out</a>
            </div>
          </div>
        </div>
      </div>

      <div class="content-wrapper">
        <slot/>
      </div>

      <div class="mb-2 d-flex justify-content-center pt-3 gplay-container">
        <a href="https://play.google.com/store/apps/details?id=com.hit_pay.hitpay&hl=en_SG" target="_blank" title="Play Store" class="mr-2">
          <img src="/hitpay/images/play-store.png" height="35">
        </a>

        <a href="https://apps.apple.com/sg/app/hitpay/id1153486894" target="_blank" title="Play Store">
          <img src="/hitpay/images/app-store.svg" height="35">
        </a>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import LeftSideMenu from './LeftSideMenu'
import AnnounceKit from "announcekit-vue";

export default {
  name: 'MainLayout',
  components: {
    LeftSideMenu,
    AnnounceKit
  },
  props: {
    title: String,
    alert_text: String,
    alert_link: String,
    alert_link_text: String,
    type: String,
    user_role: String,
    nolt_link: String,
  },
  data () {
    return {
      business: window.Business,
      user: window.User,
      csrf: document.head.querySelector('meta[name="csrf-token"]').content
    }
  },
  computed: {
    isAdmin () {
      return Boolean(this.user_role)
    }
  },
  mounted() {
    this.postHogCaptureData('', this.business.id, this.business.email, '');
    $('#alertModal').modal('show');

    var APP_ID = "dwbpydma";
    var current_user_email = this.business.email;
    var current_user_id = this.business.id;

    window.intercomSettings = {
        app_id: APP_ID,
        email: current_user_email, // Email address
        user_id: current_user_id // current_user_id
      };

    (function(){var w=window;var ic=w.Intercom;if(typeof ic==="function"){ic('reattach_activator');ic('update',w.intercomSettings);}else{var d=document;var i=function(){i.c(arguments);};i.q=[];i.c=function(args){i.q.push(args);};w.Intercom=i;var l=function(){var s=d.createElement('script');s.type='text/javascript';s.async=true;s.src='https://widget.intercom.io/widget/' + APP_ID;var x=d.getElementsByTagName('script')[0];x.parentNode.insertBefore(s, x);};if(document.readyState==='complete'){l();}else if(w.attachEvent){w.attachEvent('onload',l);}else{w.addEventListener('load',l,false);}}})();


    // s.onload = function() {
    //     CreateWhatsappChatWidget(options);
    // };


    // var x = document.getElementsByTagName('script')[0];
    // x.parentNode.insertBefore(s, x);


  },

  methods: {
    async doLogout () {
      await axios.post(`/logout`, {
        csrf: this.csrf
      })

      window.location.href = this.getDomain('', 'dashboard')
    }
  }
}
</script>

<style lang="scss">
.announcekit{
  .announcekit-widget-badge{
    font-size: 17px;
    top: -1px;
  }
}
.main-layout-content {
  min-height: 100vh;
  background-color: #F3F5F8;

  @media screen and (max-width: 768px) {
    .help-line {
      line-height: 1.2;
    }
  }

  @media screen and (min-width: 768px) {
    margin-left: 300px;
  }

  .help-line {
    background-color: white;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2);
    border-bottom: 1px solid lightgrey;
    .page-title {
      font-size: 24px;
      color: #4A4A4A;
    }
  }

  .content-wrapper {
    padding: 32px 32px 100px 32px;

    @media screen and (max-width: 576px) {
      padding: 8px 8px 100px 8px;
    }
  }

  .gplay-container {
    border-top: .5px solid #979797;
    margin-left: 32px;
    margin-right: 32px;
  }

  .logout-item {
    color: red;
    cursor: pointer;
  }
}

.menu-items-mobile{
    @media screen and (max-width: 768px) {
        display: block!important;
    }
}
.menu-items-desktop{
    @media screen and (max-width: 768px) {
        display: none!important;
    }
}
</style>

