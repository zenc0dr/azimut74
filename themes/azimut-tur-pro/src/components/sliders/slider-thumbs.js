import Swiper from 'swiper/bundle';

import "@fancyapps/fancybox/dist/jquery.fancybox";

$('[data-fancybox]').fancybox({
   protect: false
});

//Fancybox.bind("[data-fancybox]", {
//   // Your options go here
//   protect: true
//});
$.fancybox.defaults.backFocus = false;

export function acceptSwipers() {
   if (!window.jQuery && !window.Swiper) {
      setTimeout(() => {
         acceptSwipers()
      }, 100)
      return
   }

   let swipers = $('section[swiper]:not([loaded])')
   if (!swipers) return
   for (let i = 0; i < swipers.length; i++) {

      let swiper = swipers.eq(i)
      let swiper_class = swiper.attr('swiper');


    //  $(`.${swiper_class} a`).fancybox({
    //     protect: false,
    //     thumbs: false
    //  });

      let is_vertical = $(`.${swiper_class} .thumbSlider`).attr('vertical');

      window['swiper_' + swiper_class] = new Swiper(`.${swiper_class} .thumbSlider`, {
         loop: false,
         spaceBetween: 10,
         slidesPerView: 4,
         freeMode: true,
         direction: (is_vertical) ? 'vertical' : 'horizontal',
         watchSlidesVisibility: true,
         watchSlidesProgress: false,
         navigation: {
            nextEl: "." + swiper_class + " .thumbSlider .swiper-button-next",
            prevEl: "." + swiper_class + " .thumbSlider .swiper-button-prev",
         },
         breakpoints: {
            0: {
               slidesPerView: 3,
            },
            568: {
               slidesPerView: (is_vertical) ? 3 : 4,
            },
         }
      })

      new Swiper("." + swiper_class + " .shipSlide", {
         loop: false,
         spaceBetween: 10,
         navigation: {
            nextEl: "." + swiper_class + " .swiper-button-next",
            prevEl: "." + swiper_class + " .swiper-button-prev",
         },
         thumbs: {
            swiper: window['swiper_' + swiper_class],
         },
      })

      let parent = $('.' + swiper_class).parents('.tab-item.close-mobile');
      if (parent) {
         parent.on('click', function () {
            window['swiper_' + swiper_class].update();
         });
      }
      swiper.attr('loaded', true);
   }
}

