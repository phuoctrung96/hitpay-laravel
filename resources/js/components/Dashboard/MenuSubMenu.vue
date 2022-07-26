<template>
  <div class="menu-sub-item">
    <MenuItem
      :item="item"
      :forceSelect="childSelected"
      :expanded="expanded"
      @click="$emit('expand')"/>
    <div class="list-sub-items">
      <template v-if="expanded">
        <div      
          v-for="(child, childIndex) in item.children"
          :key="childIndex"
          class="sub-item">

          <MenuItem
            :item="child"/>
        </div>
      </template>
    </div>
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
  mounted(){
    if(this.item.children.some(child => window.location.href.includes(child.path))){
      this.expanded = true;
    }
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
  .list-sub-items{
    padding: 0px 0px 0px 40px;
    position: relative;
    &:before{
      content: '';
      display: block;
      width: 1px;
      background-color: rgba(255, 255, 255, 0.15);
      position: absolute;
      left: 22px;
      top: 8px;
      bottom: 5px;
    }
    .menu-item{
      padding: 8px 10px 8px 10px;
      .icon-current{
        display: none;
      }
    }
  }
}
</style>