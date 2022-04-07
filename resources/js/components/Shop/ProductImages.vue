<style scoped>
  .secondary-slide {
    margin-top: 15px;
  } 
  .splide--nav > .splide__track > .splide__list > .splide__slide {
    border: none;
    opacity: 0.5;
    cursor:pointer;
    transition:opacity .2s cubic-bezier(.54,.01,.1,1);
  }
  .splide--nav > .splide__track > .splide__list > .splide__slide.is-active{
    opacity: 1;
  }
  .splide__slide img{
    width: 100%;
  }
</style>

<template>
  <div>
    <splide :options="primaryOptions" class="primary-slider" ref="primary" @splide:moved="onMoved" @splide:active="onActive">
        <splide-slide v-for="(image, index) in images" :key="index">
            <img :data-splide-lazy="image" alt="slide.alt" class="primary-image">
        </splide-slide>
     </splide>
     <splide :options="secondaryOptions" ref="secondary" v-if="images.length > 1" class="secondary-slide">
      <splide-slide v-for="(image, index) in images" :key="index">
        <img :src="image" alt="slide.alt">
      </splide-slide>
    </splide>
  </div>
</template>

<script>
import {Splide, SplideSlide} from '@splidejs/vue-splide';
import '@splidejs/splide/dist/css/themes/splide-default.min.css';

export default {
    components: {
        Splide,
        SplideSlide,
    },
    props: {
        images: {
            required: true
        },
    },
    methods: {
      onActive( splide ) {
        var heightFirstItem = $('.primary-slider .splide__track .splide__slide.is-active img').height();
        $('.primary-slider .splide__track .splide__list').height(heightFirstItem);
      },
      onMoved( splide ) {
        var heightCurrrentItem = $('.primary-slider .splide__track .splide__slide.is-active img').height();
        $('.primary-slider .splide__track .splide__slide.is-active').height(heightCurrrentItem);
        $('.primary-slider .splide__track .splide__list').height(heightCurrrentItem);
      },
    },
    data() {
      return {
        primaryOptions: {
          pagination : false,
          arrows     : false,
          lazyLoad: 'nearby',
	      },
	      secondaryOptions: {
		      rewind      : true,
          fixedWidth  : 100,
          fixedHeight : 100,
          isNavigation: true,
          arrows     : false,
          gap         : 10,
          focus       : 'center',
          pagination  : false,
          cover       : true,
          breakpoints : {
            '992': {
              fixedWidth  : 80,
              fixedHeight : 80,
            }
          }
	      },
			};
    },
    mounted() {
		  // Set the sync target.
      this.$refs.primary.sync( this.$refs.secondary.splide );
    },
}
</script>
