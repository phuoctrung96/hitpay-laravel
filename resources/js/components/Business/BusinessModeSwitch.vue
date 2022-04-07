<template>
    <!-- <div class="business-mode-switch d-flex justify-content-between align-items-center"> -->
    <div class="business-switch-option d-flex">
        <div v-for="(option, index) in options" :key="index" class="item">
            <label class="label-checkbox">
                <input type="radio" :value="option" :v-model="business_type" :checked="option == 'company'"  :name="business_type" @click="onClick(option)"> {{ option }}
                <span class="checkmark"></span>
            </label>
        </div>
    </div>
</template>

<script>
export default {
    name: 'BusinessModeSwitch',
    props: {
        options: {
            type: Array,
            default: () => [
                'company',
                'individual',
            ]
        },
        value: String
    },
    data() {
        return {
            business_type: 'company'
        }
    },
    methods: {
        onClick(option) {
            this.$emit('input', option)
        }
    }
}
</script>

<style lang="scss" scoped>
.business-mode-switch {
    height: 44px;
    border-radius: 24px;
    background: linear-gradient(#9C9FB5, #BBBBCE);
    box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
    position: relative;

    .option {
        text-transform: capitalize;
        flex: 0 0 calc(50% - 8px);
        margin: 4px;
        height: 36px;
        border-radius: 19px;
        font-size: 19px;
        transition: background-color .2s linear;
        z-index: 2;

        &:not(.selected) {
            color: white;
            cursor: pointer;
        }
    }

    .switch-back {
        position: absolute;
        top: 0;
        left: 0;
        margin: 4px;
        height: 36px;
        border-radius: 19px;
        background-color: white;
        width: calc(50% - 8px);
        transition: left .15s linear;

        &.right {
            left: 50%;
        }
    }
}
.account-verification{
   .business-switch-option{
        margin: 0px 0px 15px -32px !important;
        .item{
            margin: 0px 0px 0px 32px;
        }
    }
} 

</style>
