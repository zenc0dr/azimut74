<template>
   <section class="sliderGallery-wrapper container">
      <div class="tinybox-gallery" v-if="!isMobile">
         <div class="tinybox-gallery__previews row">
               <div v-for="(image, i) in images"
                  @click="index = i"
                  class="col-sm-6 col-md-3 col-lg-3">
                  <div :prop="image.src" :style="`background-image:url(${ image.src })`">
                      <div class="caption">{{ image.caption }}</div>
                  </div>
               </div>
         </div>
      </div>

      <section v-else :swiper="swiperClass" class="slider-thumbs" :class="swiperClass" style="--swiper-navigation-color: #fff; --swiper-pagination-color: #fff">
         <div  class="swiper-container shipSlide">
            <div class="swiper-wrapper">
                  <div v-for="(image, i) in images"  @click="index = i" class="swiper-slide ship-img-slide"  :href="image">
                     <img :prop="image.src" :src="image.src"/>
                  </div>
            </div>
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
         </div>
         <div thumbsslider="" class="swiper-container thumbSlider">
            <div class="swiper-wrapper">
               <div v-for="(image, i) in images" @click="index = i" class="swiper-slide ship-img-slide">
                  <img :prop="image.src" :src="image.src"/>
               </div>
            </div>
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
         </div>
      </section>

       <Tinybox
            v-model="index"
            :images="images"
         />

   </section>
</template>

<script>
import Tinybox from "vue-tinybox";
import Swiper from 'swiper/bundle';

export default {
    name: "TinyboxGallery",
    components: { Tinybox },
    data() {
        return {
            index: null,
            swiperClass: '',
            isMobile: false,
            width: 0,
            images: []
        }
    },
    created() {
        window.addEventListener('resize', this.handleResize);
        this.handleResize();
    },
    destroyed() {
        window.removeEventListener('resize', this.handleResize);
    },
    mounted() {
        let json_data = $('meta[name="tinybox-gallery-data"]').attr('content')
        if (json_data) {
            this.images = JSON.parse(json_data);
            this.swiperClass = $('meta[name="tinybox-gallery-data"]').attr('swiper-class');
        }
    },
    watch: {
      width(newWidth) {
         this.isMobile = (newWidth <= 576) ? true : false
      },
      isMobile(newValue,oldValue) {
         setTimeout(() => {
             this.acceptSwipers();
         }, 1000)

      },
    },
    methods: {
      handleResize() {
         this.width = window.innerWidth;
      },

      acceptSwipers() {
         let swipers = $(`section[swiper="${this.swiperClass}"]:not([loaded])`)
         let swipersLoad = $(`section[swiper="${this.swiperClass}"][loaded]`)
         if (swipersLoad) {
              for (let i = 0; i < swipersLoad.length; i++) {
                  let swiper = swipers.eq(i)
                  swiper.destroy;
              }
         }

         if (!swipers && !swipersLoad) return;
         for (let i = 0; i < swipers.length; i++) {
            let swiper = swipers.eq(i)
            let swiper_class = swiper.attr('swiper');

            window['swiper_' + swiper_class] = new Swiper(`.${this.swiperClass} .thumbSlider`, {
               loop: false,
               spaceBetween: 10,
               slidesPerView: 4,
               freeMode: true,
               watchSlidesVisibility: true,
               watchSlidesProgress: true,
               navigation: {
                  nextEl: "." + swiper_class + " .thumbSlider .swiper-button-next",
                  prevEl: "." + swiper_class + " .thumbSlider .swiper-button-prev",
               },
               breakpoints: {
                  0: {
                     slidesPerView: 3,
                  },
                  568: {
                     slidesPerView: 4,
                  }
               }
            })

            new Swiper("." + this.swiperClass + " .shipSlide", {
               loop: false,
               spaceBetween: 10,
               navigation: {
                  nextEl: "." + this.swiperClass + " .swiper-button-next",
                  prevEl: "." + this.swiperClass + " .swiper-button-prev",
               },
               thumbs: {
                  swiper: window['swiper_' + this.swiperClass],
               },
            })

            swiper.attr('loaded', true);
         }
      }
    }
}
</script>
