<template>
    <div
        class="login-input">
        <span class="label">{{ label }}</span>
        <select
            :value="value"
            class="form-control selectpicker"
            :class="{ 'is-invalid': error}"
            :data-live-search="search ? true : false"
            :autofocus="autofocus"
            :disabled="disabled"
            @change="$emit('input', $event.target.value)"
        >
            <option value="" selected></option>
            <option v-for="option in options"
                    :data-tokens="search ? option[optionDisplay] : ''"
                    :value="optionValue ? option[optionValue] : option">
                {{ optionDisplay ? option[optionDisplay] : option }}
            </option>
        </select>
        <span class="small text-muted">{{ helper }}</span>
        <div class="invalid-feedback error d-block">
            {{ error }}
        </div>
    </div>
</template>

<script>
import 'bootstrap-select';
import 'bootstrap-select/dist/css/bootstrap-select.css';

export default {
    name: 'LoginSelect',
    props: {
        label: String,
        value: String,
        helper: String,
        search: {
            type: Boolean,
            default: false
        },
        autofocus: {
            type: Boolean,
            default: false
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
        options: Array,
        optionValue: {
            type: String,
        },
        optionDisplay: {
            type: String,
        }
    },
}

</script>

<style lang="scss" scoped>
.login-input {
    margin: 0px 0px 20px;
    .label {
        font-size: 14px;
        font-weight: 500;
    }

    select:not(.is-invalid), .bootstrap-select {
        border: 1px solid #9B9B9B !important;
    }

    .error {
        position: relative;
        top: 5px;
    }

}
</style>
<style>
.dropdown-menu.inner {
    min-height: 0 !important;
}
</style>
