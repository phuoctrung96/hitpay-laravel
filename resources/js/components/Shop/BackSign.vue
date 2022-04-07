<template>
  <!-- We use inline svg because we want to dynamically change color (theme) -->
  <svg width="18px" height="24px" viewBox="0 0 18 24" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
    <g id="Checkout" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
      <g id="Post-Select" transform="translate(-27.000000, -32.000000)">
        <g id="left-arrow-2" transform="translate(27.000000, 32.000000)" fill-rule="nonzero">
          <polygon id="Shape" points="11.9891014 0.0258550725 0 12.0068986 11.9851014 23.988 13.3968696 22.5802319 3.82197101 13.0053333 17.9558841 13.0053333 17.9558841 11.0085217 3.82197101 11.0085217 13.4008696 1.42962319"></polygon>
        </g>
      </g>
    </g>
  </svg>  
</template>

<script>
export default {
  name: 'BackSign'
}
</script>

<style lang="scss" scoped>
svg g {
  fill: inherit;
}
</style>