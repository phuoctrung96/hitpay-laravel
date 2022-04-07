<template>
  <div class="table-card d-flex flex-column">
    <div class="d-flex justify-content-between title">
      {{ title }}
      <a :href="link">VIEW ALL</a>
    </div>

    <div class="content ml-3 flex-grow-1">
      <slot/>
    </div>
  </div>
</template>

<script>
export default {
  name: 'TableCard',
  props: {
    title: String,
    link: String
  }
}
</script>

<style lang="scss" scoped>
.table-card  {
  border-radius: 6px;
  background-color: white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, .2);
  min-width: 300px;
  min-height: 250px;

  .title {
    padding: 12px;
    border-bottom: .5px solid #979797;
    font-size: 14px;
    color: #4A4A4A;

    a {
      font-weight: 600;
      color: #9B9B9B;
    }
  }
}
</style>
