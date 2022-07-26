<template>
  <div
    ref="container"
    class="sliding-digit">
    <div
      class="digits"
      :style="digitsStyle">
      <div
        v-for="i in 10"
        :key="i"
        class="digit d-flex justify-content-center">
        {{ i - 1 }}
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'SlidingDigit',
  props: {
    value: {
      type: Number,
      required: true
    },
    height: {
      type: Number,
      default: -1
    }
  },
  data () {
    return {
      mounted: false,
      actualHeight: 0,
      observer: null
    }
  },
  computed: {
    digitsStyle () {
      const height = this.mounted
        ? this.finalHeight
        : 0

      return {
        // on first run ref will be undefined because component did not mounted yet
        top: `${-height * this.value}px`
      }
    },
    finalHeight () {
      return this.height > 0
        ? this.height
        : this.actualHeight
    }
  },
  mounted () {
    this.mounted = true

    this.observer = new ResizeObserver(p => {
      this.actualHeight = this.$refs.container.offsetHeight
    })

    this.observer.observe(this.$refs.container)

  },
  beforeDestroy () {
    this.observer.unobserve(this.$refs.container)
  }
}
</script>

<style lang="scss">
.sliding-digit {
  position: relative;
  overflow: hidden;

  .digits {
    position: absolute;
    left: 0;
    right: 0;
    display: flex;
    flex-direction: column;
    transition: top .5s;
    transition-timing-function: ease-in-out;

    .digit {
      width: 100%;
    }
  }
}
</style>