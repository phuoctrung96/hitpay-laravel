<template>
  <div
    class="draggable-method d-flex justify-content-between align-items-center"
    :class="{ dragged }"
    @mousedown="$emit('startDrag', { method: value, event: $event })">
    {{ methodNames[value] }} <i class="fas fa-grip-vertical ml-2"/>
  </div>
</template>

<script>
import MethodNamesMixin from './MethodNamesMixin'

export default {
  name: 'DraggableMethod',
  mixins: [
    MethodNamesMixin
  ],
  props: {
    value: String,
    dragged: {
      type: Boolean,
      default: false
    }
  }
}
</script>

<style lang="scss" scoped>
.draggable-method {
  padding: 7px 10px;
  border-radius: 8px;
  margin-right: 8px;
  box-shadow: rgba(99, 99, 99, 0.2) 0px 2px 10px 0px;
  cursor: pointer;
  user-select: none;
  width: 110px;
  font-size: 16px;
  font-weight: 500;

  &.dragged {
    background-color: rgba(0, 0, 0, .2);
  }
}
</style>
