import '@src/components/dolphin/ext-offers-widget.js';
import '@src/components/dolphin/faq.js';
import '@src/components/blocks/page-groups/_.js';
import '@src/components/hotels/_';
import '@src/components/print-btn/_';
import '@src/components/blocks/tabs/no-swiper.js';

import * as sliderThumbs from '@components/sliders/slider-thumbs';

$(window).on('load', function () {
    if ($('section[swiper]:not([loaded])').length) {
        sliderThumbs.acceptSwipers();
    }
});


