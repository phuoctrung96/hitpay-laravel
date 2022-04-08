<template>
    <div class="business-mode-switch d-flex justify-content-between align-items-center">
        <div
            v-for="(option, index) in options"
            :key="index"
            class="option d-flex justify-content-center align-items-center"
            :class="{ selected: option === value }"
            @click="onClick(option)">
            {{ option }}
        </div>

        <div
            class="switch-back"
            :class="{ right: value === options[1] }"/>
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
    methods: {
        onClick(option) {
            if (option !== this.value) {
                this.$emit('input', option)
            }
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
</style>
