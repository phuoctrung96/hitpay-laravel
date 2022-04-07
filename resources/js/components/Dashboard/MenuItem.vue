<template>
  <a
    class="menu-item clearfix"
    :class="{ current }"
    :href="this.item.path"
    @click="onClick">
    <div
      class="icon-current"
      :class="{ current }">

      <svg
        v-if="item.icon"
        width="16"
        height="16"
        viewBox="0 0 20 20">      
        <use :xlink:href="`/icons/left-menu/${item.icon}#hitpay`"></use>
      </svg>
    </div>
    <div class="text-menu">{{ item.title }} <span v-if="item.child_title" :id="this.item.id"> {{ item.child_title }}</span></div>
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
.hidden-try-me {
  display: none !important;
}
.menu-item {
  font-size: 15px;
  cursor: pointer;
  color: white;
  display: block;
  padding: 8px 15px 8px 15px;
  width: 100%;
  border-radius: 6px;
  text-decoration: none;
  &.current, &:hover {
    background: rgba(255, 255, 255, 0.15);
    color: #FFF;
  }
  .icon-current {
    height: 100%;
    width: 27px;
    float: left;
    &.current {
      
    }
  }
  .text-menu{
    overflow: hidden;
  }

  span{
      background: #d836bf;
      font-size: 9px;
      text-transform: uppercase;
      display: inline-block;
      padding: 3px 5px 3px 5px;
      font-weight: 600;
      margin: 0px 0px 0px 6px;
      border-radius: 5px;
    }
}
</style>