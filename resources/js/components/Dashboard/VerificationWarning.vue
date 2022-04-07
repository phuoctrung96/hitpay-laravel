<template>
    <div
        v-if="value"
        class="verification-warning-panel">

        <div class="container d-flex flex-column align-items-center">
            <div class="d-flex flex-column">
                <span class="title text-center">Account Verification</span>

                <span class="info-line text-center my-5">Please finish your account verification</span>
            </div>

            <div class="d-flex flex-column align-items-center mt-5">
                <template v-if="page === 0">
                    <CheckoutButton
                        title="Next"
                        @click="page = 1"
                        class="next-button"/>
                </template>

                <template v-if="page === 1">
                    <img :src="individual ? '/icons/singpass_color.png' : '/icons/myinfosg_color.svg'" height="38"/>

                    <a
                        :href="verificationLink"
                        role="button">
                        <img
                            class="mt-5"
                            height="60" width="168"
                            :src="individual ? '/icons/myinfo_button_i.png' : '/icons/myinfo_business_button.svg'"/>
                    </a>

                    <span
                        v-if="!individual"
                        class="info-line mt-2 text-center">I agree that I am a director/shareholder/ Sole proprietor</span>
                  <p class="mt-2 mx-auto">
                    <span>OR</span>
                  </p>
                  <a :href="manualLink" class="btn btn-primary mt-2 mx-auto btn-block">Enter Manually</a>
                </template>
            </div>
        </div>
    </div>
</template>

<script>
import CheckoutButton from '../Shop/CheckoutButton'

export default {
    name: 'VerificationWarning',
    components: {
        CheckoutButton
    },
    props: {
        value: {
            type: Boolean,
            default: false
        },
        businessId: String,
        business: Object
    },
    data () {
        return {
            mode: 'individual',
            page: 1
        }
    },
    computed: {
        individual () {
            return this.mode === 'individual'
        },
        verificationLink () {
            const path = this.individual
                ? 'personal'
                : 'business'

            return this.getDomain(`business/${this.businessId}/verification/${path}/redirect`, 'dashboard')
        },
        manualLink(){
          return this.getDomain(`business/${this.businessId}/verification/manual/${this.mode}`, 'dashboard')
        }
    },

    mounted() {
        if (this.business.business_type == 'individual') {
            this.mode = 'individual';
        } else {
            this.mode = 'company';
        }
    }
}
</script>

<style lang="scss">
.verification-warning-panel {
    position: fixed;
    bottom: 0;
    top: 0;
    left: 0;
    right: 0;
    backdrop-filter: blur(12px) brightness(70%);
    display: flex;
    flex-direction: column;
    justify-content: center;
    z-index: 3;

    .container {
        background-color: white;
        color: #4A4A4A;

        .next-button {
            width: 150px;
        }

        @media screen and (max-width: 576px) {
            width: 100%;
            height: 100%;
            padding: 72px 32px;
        }

        @media screen and (min-width: 576px) {
            box-shadow: 0px 0 10px rgba(0, 0, 0, 0.3);
            border-radius: 24px;
            width: 487px;
            height: 600px;
            padding: 32px;
        }

        .title {
            font-size: 20px;
            font-weight: 300;
        }

        .info-line {
            font-size: 14px;
        }


        .switch {
            @media screen and (max-width: 576px) {
                width: 300px;
            }

            @media screen and (min-width: 576px) {
                width: 364px;
            }
        }

        .skip-button {
            font-size: 12px;
            text-transform: uppercase;
            text-decoration: underline;
        }
    }
}
</style>
