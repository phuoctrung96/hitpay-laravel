<template>
    <div
        class="login-input">
        <span class="label">{{ label }}</span>
        <div class="is-form-control">
            <input
                :value="value"
                @input="$emit('input', $event.target.value)"
                :type="type"
                class="form-control"
                :class="{ 'is-invalid': error }"
                :placeholder="placeholder"
                :autocomplete="autocomplete"
                :autofocus="autofocus"
                :disabled="disabled">
            <span class="sh-password show" v-if="isPassword && !isShowPassword" @click="switchVisibility()"><img src="/images/ico-show-password.svg"></span>
            <span class="sh-password hide" v-if="isPassword && isShowPassword" @click="switchVisibility()">
                <img src="/images/ico-hide-password.svg">
            </span>
        </div>
        <span class="small text-muted">{{helper}}</span>
        <div class="invalid-feedback error d-block">
            {{ error }}
        </div>
    </div>
</template>

<script>
export default {
    name: 'LoginInput',
    props: {
        label: String,
        value: String,
        placeholder: String,
        helper: String,
        autocomplete: {
            type: String,
            default: 'off'
        },
        autofocus: {
            type: Boolean,
            default: false
        },
        type: {
            type: String,
            default: 'text'
        },
        error: String,
        disabled: {
            type: Boolean,
            default: false
        },
        marginBottom: {
            type: Number,
            default: 0
        },
        isPassword: {
            type: Boolean,
            default: false
        },
        isShowPassword: {
            type: Boolean,
            default: false
        }
    },
    methods: {
        switchVisibility() {
            if(this.isShowPassword) {
                this.isShowPassword = false;
                this.type = "password";
            }else {
                this.isShowPassword = true;
                this.type = "text";
            }
        }
    }
}
</script>

<style lang="scss" scoped>
.login-input {
    .is-form-control{
        position: relative;
        span{
            font-size: 0;
            &.sh-password{
                position: absolute;
                right: 16px;
                top: 50%;
                margin-top: -6px;
                cursor: pointer;
                &.show{
                    img{
                        max-width: 17px;
                        height: auto;
                    }
                }
                &.hide{
                    margin-top: -9px;
                    img{
                        max-width: 17px;
                        height: auto;
                    }
                }
            }
        }
    }
    .label{
        font-size: 13px;
        font-weight: 500;
    }

    .form-control{
        margin-bottom: 15px;
    }

    .error {
        position: relative;
        top: -5px;
    }
}
</style>
