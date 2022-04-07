<template>
  <div class="payment-method-card d-flex flex-column justify-content-between ml-xl-4">
    <span class="pmc-title">
      {{ title }}
    </span>

    <span class="pmc-text text-center">
      {{ text }}
    </span>

    <SmallButton
      :title="button"
      :href="link"
      class="align-self-end"/>
  </div>
</template>

<script>
import SmallButton from '../UI/SmallButton'

export default {
  name: 'PaymentMethodCard',
  components: {
    SmallButton
  },
  props: {
    title: String,
    text: String,
    button: String,
    link: String
  }
}
</script>

<style lang="scss">
.payment-method-card {
  border-radius: 6px;
  background: linear-gradient(#9AC4F8, #4A90E2);
  padding: 16px 32px 12px 32px;
  height: 211px;
  width: 45%;
  min-width: 400px;

  @media screen and (max-width: 1200px) {
    width: 100%;
    min-width: 100%;
  }

  .pmc-title {
    color: white;
    font-size: 18px;
    font-weight: 600;
  }

  .pmc-text {
    font-size: 14px;
    max-width: 324px;
    align-self: center;
  }
}
</style>
