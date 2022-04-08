<template>
  <div class="menu-sub-item">
    <MenuItem
      :item="item"
      :forceSelect="childSelected"
      @click="$emit('expand')"/>

    <template v-if="expanded">
      <div      
        v-for="(child, childIndex) in item.children"
        :key="childIndex"
        class="d-flex flex-column">

        <MenuItem
          :item="child"/>
      </div>
    </template>
  </div>  
</template>

<script>
import MenuItem from './MenuItem'

export default {
  name: 'MenuSubMenu',
  components: {
    MenuItem
  },
  props: {
    expanded: Boolean, // expanded status
    item: Object
  },
  computed: {
    childSelected () {
      return !this.expanded && this.item.children && this.item.children.some(child => window.location.href.endsWith(child.path))
    }
  }
}
</script>

<style lang="scss">
.menu-sub-item {
}
</style>