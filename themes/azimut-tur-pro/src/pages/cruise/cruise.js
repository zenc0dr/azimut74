//import '@plugins/mcmraak/rivercrs/assets/js/pro-kruiz.booking.js'

import '@components/blocks/tabs/_';
import '@components/cruise/tab-booking-2';
import '@components/zen-gallery/zen-gallery.js';
import '@src/components/reviews-widget/_.js';
import axios from "axios";

const REVIEWS_ANCHOR = '#page-reviews-widget';

function scrollToCruiseReviews() {
    const el = document.querySelector(REVIEWS_ANCHOR);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

document.addEventListener('click', (e) => {
    const a = e.target.closest('a.cruise__item-left_reviews-link');
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

function updateTopBookingPriceFromCache() {
    const bookingBtn = document.querySelector('.booking-btn.btn.red[data-checkin-id][data-eds-code]');
    if (!bookingBtn) {
        return;
    }

    const edsCode = (bookingBtn.dataset.edsCode || '').toLowerCase();
    if (edsCode !== 'gama') {
        return;
    }

    const checkinId = parseInt(bookingBtn.dataset.checkinId, 10);
    if (!checkinId) {
        return;
    }

    const priceNode = bookingBtn.querySelector('span');
    if (!priceNode) {
        return;
    }

    axios.get(`/rivercrs/api/v2/exist/min-price/${checkinId}`)
        .then((response) => {
            const payload = response?.data || {};
            const minPrice = parseInt(payload.min_price, 10);
            if (!payload.success || !minPrice) {
                return;
            }

            // Для этой доработки цена на кнопке выводится без разделителя разрядов.
            priceNode.textContent = String(minPrice);
        })
        .catch(() => {
            // Оставляем серверное fallback-значение без изменений.
        });
}

updateTopBookingPriceFromCache();
