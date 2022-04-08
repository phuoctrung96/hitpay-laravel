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

    <div class="main-content d-flex flex-column flex-grow-1 p-2 p-md-5">
        <div class="row mb-lg-5">
            <div class="col-md-12">
                <div class="wrapper-progressBar">
                    <ul class="progressBar">
                        <li :class="{'active': item.active}" v-for="(item, itemIndex) in this.steps">
                            {{ item.name }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <h3>{{ title }}</h3>
            </div>
        </div>
        <slot/>
    </div>
  </div>
</template>

<script>
export default {
    name: 'OnboardLayout',

    props: {
      title: String,
      step: String,
    },

    data() {
        return {
            steps : [
                {
                    id: 1,
                    name: 'Create Account',
                    active: 0,
                },
                {
                    id: 2,
                    name: 'Create Business',
                    active: 0,
                },
                {
                    id: 3,
                    name: 'Bank Setup',
                    active: 0,
                },
            ]
        }
    },

    mounted() {
        let that = this;

        this.steps.forEach(function(value, key) {
            if (that.step == 'step1') {
                if (value.id == 1) {
                    value.active = true;
                }
            } else if (that.step == 'step2') {
                if (value.id == 1 || value.id == 2) {
                    value.active = true;
                }
            } else if (that.step === 'step3') {
                if (value.id == 1 || value.id == 2 || value.id == 3) {
                    value.active = true;
                }
            }
        })
    },
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

      .header {
        font-size: 25px;
        margin-bottom: 50px;
      }

      .bottom-link {
        font-size: 16px;
        color: #4A4A4A;
      }
    }
  }
}

.wrapper-progressBar {
    width: 100%;
}

.progressBar {
}

.progressBar li {
    list-style-type: none;
    float: left;
    width: 33%;
    position: relative;
    text-align: center;
}

.progressBar li:before {
    content: " ";
    line-height: 30px;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    border: 1px solid #ddd;
    display: block;
    text-align: center;
    margin: 0 auto 10px;
    background-color: white
}

.progressBar li:after {
    content: "";
    position: absolute;
    width: 100%;
    height: 4px;
    background-color: #ddd;
    top: 15px;
    left: -50%;
    z-index: -1;
}

.progressBar li:first-child:after {
    content: none;
}

.progressBar li.active {
    color: dodgerblue;
}

.progressBar li.active:before {
    border-color: dodgerblue;
    background-color: dodgerblue
}

.progressBar .active:after {
    background-color: dodgerblue;
}

</style>
