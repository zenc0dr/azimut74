//import '@plugins/mcmraak/rivercrs/assets/js/pro-kruiz.booking.js'

import '@components/blocks/tabs/_';
import '@components/cruise/tab-booking-2';
import '@components/zen-gallery/zen-gallery.js';
import '@src/components/reviews-widget/_.js';

const REVIEWS_ANCHOR = '#page-reviews-widget';

function scrollToCruiseReviews() {
    const el = document.querySelector(REVIEWS_ANCHOR);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

document.addEventListener('click', (e) => {
    const a = e.target.closest('a.cruise-reviews-jump__link');
    if (!a || a.getAttribute('href') !== REVIEWS_ANCHOR) {
        return;
    }
    e.preventDefault();
    const tab1 = document.querySelector('.tabs .tab[tab="1"]');
    if (tab1 && !tab1.classList.contains('active')) {
        tab1.click();
        window.setTimeout(scrollToCruiseReviews, 50);
    } else {
        scrollToCruiseReviews();
    }
});
