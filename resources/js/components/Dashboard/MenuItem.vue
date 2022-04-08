<template>
  <a
    class="menu-item d-flex align-items-center"
    :class="{ current }"
    :href="this.item.path"
    @click="onClick">
    <div
      class="icon-current d-flex align-items-center"
      :class="{ current }">

      <svg
        v-if="item.icon"
        width="16"
        height="16"
        viewBox="0 0 20 20">      
        <use :xlink:href="`/icons/left-menu/${item.icon}#hitpay`"></use>
      </svg>
    </div>
    {{ item.title }}
  </a>
</template>

<script>
export default {
  name: 'MenuItem',
  props: {
    item: Object,
    forceSelect: {
      type: Boolean,
      default: false
    }
  },
  computed: {
    current () {
      return this.forceSelect ||
        (this.item.path && window.location.href.endsWith(this.item.path)) ||
        (this.item.additionalPaths && this.item.additionalPaths.some(path => window.location.href.endsWith(path)))
    }
  },
  methods: {
    onClick (event) {
      if (this.item.action) {
        event.preventDefault()
        this.item.action()
      } else {
        if (!this.item.path) {
          this.$emit('click')
        }
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.menu-item {
  font-size: 16px;
  height: 32px;
  cursor: pointer;
  color: white;

  &.current {
    background-color: #23418E;
    color: white !important;
    text-decoration: none !important;
  }

  &:hover {
    color: rgb(172, 223, 209);
    text-decoration: underline;
  }

  .icon-current {
    height: 100%;
    width: 78px;
    border-left: 10px solid transparent;
    padding-left: 26px;

    &.current {
      border-left: 10px solid #C5EFCB;
    }
  }
}
</style>