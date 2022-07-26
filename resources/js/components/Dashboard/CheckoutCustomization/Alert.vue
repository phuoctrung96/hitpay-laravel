<template>
  <div
    v-if="value"
    class="alert w-100"
    :class="{ 'alert-success': !error, 'alert-danger': error }">
    {{ value }}
  </div>  
</template>

<script>
export default {
  name: 'Alert',
  props: {
    value: String,
    error: Boolean
  }
}
</script>