<template>
    <div class="mb-2 flex-grow-1">
        <div
            class="payment-system"
            :class="{ selected, disabled }"
            @click="onClick">

            <div
                v-if="disabled"
                class="fader"/>

            <transition name="widen">
                <div
                    v-if="selected"
                    class="tick">

                    <div class="tick-inner">
                        <svg
                            width="18"
                            height="14"
                            viewBox="0 0 18 14">
                            <use xlink:href='/images/tick.svg#tick'></use>
                        </svg>
                    </div>
                </div>
            </transition>

            <img
              :src="`/icons/payment-methods/${imageUrl}`"
              :height="setHeight ? '23px' : 'auto'"/>
        </div>
        <div v-if="cashback || campaign_rule" class="p-1 mx-auto mt-2 cashback-box">{{ campaign_rule ? campaignCashbackName : cashbackName }}</div>
    </div>
</template>

<script>
export default {
    name: 'PaymentMethod',
    props: {
      imageUrl: String,
      selected: Boolean,
      disabled: Boolean,
      cashback: Object,
      campaign_rule: Object,
      setHeight: {
        type: Boolean,
        default: false
      }
    },
    methods: {
        onClick() {
            if (!this.disabled) {
                this.$emit('click')
            }
        },
    },
    computed: {
      cashbackName() {
          if (this.cashback) {
              return (this.cashback.percentage ? this.cashback.percentage + "%" : '') + (this.cashback.fixed_amount ? ' + $' + (this.cashback.fixed_amount/100).toFixed(2) : '' +' Cashback');
          }
          else return '';
      },
      campaignCashbackName(){
        return (this.campaign_rule.cashback_amt_percent > 0 ? this.campaign_rule.cashback_amt_percent  + "%" : '') + (this.campaign_rule.cashback_amt_percent > 0 && this.campaign_rule.cashback_amt_fixed ? ' + ' : '') + (this.campaign_rule.cashback_amt_fixed ? '$' + this.campaign_rule.cashback_amt_fixed : '') + ' Cashback With OCBC PayAnyone';
      }
    }
}
</script>

<style lang="scss">
$tickRadius: 12px;
$tickMargin: 14px;
$disabledColor: rgba(0, 0, 0, .15);

.cashback-box{
    border-radius: 4px;
    font-size: 12px;
    width: 75%;
    background-color: #a9b4d4;
    text-align: center;
}

.payment-system {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 7px 10px;
    border-radius: 8px;
    margin: 0 7px;
    box-shadow: rgba(99, 99, 99, 0.2) 0px 2px 10px 0px;
    cursor: pointer;
    background-color: white;
    border: solid 0.5px white;
    overflow: hidden;
    height: 39px;
    min-width: 108px;
    position: relative;

    &.disabled {
        border: solid 0.5px $disabledColor;
        box-shadow: none;
        cursor: default;
    }

    .fader {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: $disabledColor;
        z-index: 2;
    }

    .tick {
        height: 2 * $tickRadius;
        overflow: hidden;

        flex: 0 0 (2 * $tickRadius + $tickMargin);
        overflow: hidden;
        display: flex;
        justify-content: center;
        align-items: center;

        .tick-inner {
            margin-right: $tickMargin;
            background-color: rgb(216, 216, 216);
            border-radius: 16px;
            width: 2 * $tickRadius;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #011B5F;
        }
    }

    &:not(.selected):not(.disabled):hover {
        transform: translateY(-2px);
    }

    &.selected {
        cursor: default;
        background-color: #D8D8D8;
        border: solid 0.5px #979797;

        .tick .tick-inner {
            background-color: white;
        }
    }
}

.widen-enter-active,
.widen-leave-active {
    transition: all .1s ease-in;
}

.widen-enter-to, .widen-leave {
    // width + margin
    max-width: $tickRadius * 2 + $tickMargin;
    opacity: 1;
}

.widen-enter, .widen-leave-to {
    max-width: 0;
    opacity: 0;
}
</style>
