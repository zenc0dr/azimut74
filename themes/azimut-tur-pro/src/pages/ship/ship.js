import '@src/components/search-widget/_.js'
import '@components/blocks/tabs/_';
import '@src/components/reviews-widget/_.js'

const SHIP_REVIEWS_ANCHOR = '#page-reviews-widget';

function scrollToShipPageReviews() {
    const el = document.querySelector(SHIP_REVIEWS_ANCHOR);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

document.addEventListener('click', (e) => {
    const a = e.target.closest('a.ship__item-left_reviews-link');
    if (!a || a.getAttribute('href') !== SHIP_REVIEWS_ANCHOR) {
        return;
    }
    e.preventDefault();
    scrollToShipPageReviews();
});
