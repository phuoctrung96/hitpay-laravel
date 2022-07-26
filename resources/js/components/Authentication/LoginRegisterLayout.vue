<template>
  <div class="login-register-layout">
    <div class="left-panel d-flex flex-md-column justify-content-center justify-content-md-between align-items-center">
      <!-- logo -->
      <svg
        class="align-self-start"
        height="41"
        viewBox="0 0 576 144">
        <use xlink:href='/images/hitpay.svg#hitpay'></use>
      </svg>

      <span class="main-text d-none d-md-block">
        You are just a few clicks away from accepting payments
      </span>

      <div class="flex-column bottom-text d-none d-md-flex">
        <span class="text-center">Within 10 minutes, we were up & running and enjoying much lower transactional fees compared to alternate payment methods.</span>
        <span class="text-right mt-1">-Ice Cream & Cookie Co</span>
      </div>
    </div>

    <div class="main-content d-flex flex-column justify-content-center align-items-center flex-grow-1 p-2 p-md-5">
      <div class="account-verification">
        <div class="inner">
          <template v-if="['Create Account', 'Create Business', 'Bank Setup', 'Account Verification', 'Confirm your email address'].includes(title)">
            <div class="process-create">
              <div class="register-progress">
                <div class="progress enable">
                </div>
                <div class="progress" :class="(['Create Business', 'Bank Setup', 'Account Verification', 'Confirm your email address'].includes(title))? 'enable': 'disable'">
                </div>
                <div class="progress" :class="(['Create Business', 'Bank Setup', 'Account Verification'].includes(title)) ? 'enable': 'disable'">
                </div>
                <div class="progress" :class="(title === 'Account Verification')? 'enable': 'disable'">
                </div>
              </div>
            </div>
          </template>
          <div class="header">{{ title }}</div>
          <slot/>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'LoginRegisterLayout',
  props: {
    title: String
  }
}
</script>

<style lang="scss">
$leftPanelWidth: 400px;
$topPanelHeight: 60px;

.login-register-layout {
  position: relative;
  font-family: -apple-system, BlinkMacSystemFont, sans-serif;

  .left-panel {
    background-color: #011B5F;
    color: white;
    position: fixed;
    top: 0;
    bottom: 0;

    @media (max-width: 768px) {
      height: $topPanelHeight;
      width: 100%;
      padding: 8px;
    }

    @media (min-width: 768px) {
      padding: 80px 24px;
      width: $leftPanelWidth;
      box-shadow: rgba(0, 0, 0, .2) 0px 5px 10px 0px;
    }

    .main-text {
      font-size: 31px;
      font-weight: 400;
    }

    .bottom-text {
      font-size: 16px;
      font-style: italic;
      max-width: 257px;
    }
  }

  .main-content {
    min-height: 100vh;

    @media (max-width: 768px) {
      margin-top: $topPanelHeight;
    }

    @media (min-width: 768px) {
      margin-left: $leftPanelWidth;
    }

    .inner {
      @media (max-width: 768px) {
        width: 320px;
      }

      @media (min-width: 768px) {
        min-height: 570px;
        width: 364px;
      }

      .process-create {
        width: 100%;
        padding: 0px 0 40px 0;
      }

      .progress {
        width: 22%;
        height: 5px;
        float: left;
        border-radius: 15px;
        margin-right: 5px;
      }

      .enable {
        background-color: #0058FC;
      }

      .disable {
        background-color: rgba(0, 88, 252, 0.3);
      }

      .header {
        font-size: 25px;
        margin: 0px 0px 30px;
      }

      .bottom-link {
        font-size: 16px;
        color: #03102f;
        font-weight: 500;
      }
    }
  }
}
</style>
