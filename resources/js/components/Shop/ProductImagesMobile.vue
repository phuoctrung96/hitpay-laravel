<style scoped>
    .secondary-slide {
        margin-top: 15px;
    }

    .primary-image-mobile {
        width: 100%;
        height: auto;
        padding: 0px 5px 0px 5px;
    }
</style>

<template>
    <splide :options="options" class="m-slider"  @splide:moved="onMoved" @splide:active="onActive" @splide:lazyload:loaded="onLazyloadLoaded">
        <splide-slide v-for="(image, index) in images" :key="index">
            <img :data-splide-lazy="image" alt="slide.alt" class="primary-image-mobile">
        </splide-slide>
     </splide>
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
        var heightFirstItem = $('.m-slider .splide__track .splide__slide.is-active img').height();
        $('.m-slider .splide__track .splide__list').height(heightFirstItem);
      },
      onLazyloadLoaded( splide ) {
        $('.m-slider .splide__pagination').css('display', 'inline-block');
      },
      onMoved( splide ) {
        var heightCurrrentItem = $('m-slider .splide__track .splide__slide.is-active img').height();
        $('m-slider .splide__track .splide__slide.is-active').height(heightCurrrentItem);
        $('m-slider .splide__track .splide__list').height(heightCurrrentItem);
      },
    },
    data() {
      return {
        options: {
            rewind: true,
            arrows : false,
            lazyLoad: 'nearby',
            padding: {
                right : '15px',
                left  : '15px',
                }
            },
	    };
    },
}
</script>
