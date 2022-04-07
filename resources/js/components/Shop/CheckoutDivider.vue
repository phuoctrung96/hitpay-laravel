<template>
    <div class="checkout-divider">
        <span v-if="text">{{ text }}</span>
        <div class="line"/>
    </div>
</template>

<script>
export default {
    name: 'CheckoutDivider',
    props: {
        text: String
    }
}
</script>

<style lang="scss" scoped>
$height: 18px;

.checkout-divider {
    width: 100%;
    display: flex;
    justify-content: center;
    position: relative;
    height: $height;
    margin: 32px 0;

    .line {
        left: 0;
        right: 0;
        top: $height / 2;
        position: absolute;
        height: 1px;
        background-color: rgb(202, 202, 202);
    }

    span {
        z-index: 2;
        font-size: $height;
        background-color: white;
        color: rgb(57, 57, 58);
        padding: 0 ($height / 2);
    }
}
</style>