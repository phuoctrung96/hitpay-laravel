<template>
    <div
        class="login-input">
        <span class="label">{{ label }}</span>
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
        }
    }
}
</script>

<style lang="scss" scoped>
.login-input {
    .label {
        font-size: 14px;
        font-weight: 500;
    }

    input:not(.is-invalid) {
        border: 1px solid #9B9B9B;
    }

    .form-control{
        margin-bottom: 18px;
    }

    .error {
        position: relative;
        top: -5px;
    }
}
</style>
