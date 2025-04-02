import '@src/components/blocks/page-groups/_.js';
import '@src/components/hotels/_';
import '@src/components/blocks/tabs/no-swiper.js';

import '@src/components/group-tours/gt-offer-widget.js';

import * as sliderThumbs from '@components/sliders/slider-thumbs';

$(window).on('load', function () {
   if ($('section[swiper]:not([loaded])').length) {
      sliderThumbs.acceptSwipers();
   }
});

