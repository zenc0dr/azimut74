import Vue from 'vue';
import ReviewsWidget from './ReviewsWidget.vue';

Vue.config.productionTip = false;

const target = document.getElementById('reviews-widget-app');
if (target) {
    let initData = null;
    try {
        initData = target.dataset && target.dataset.init
            ? JSON.parse(target.dataset.init)
            : null;
    } catch (e) {
        initData = null;
    }

    new Vue({
        render: h => h(ReviewsWidget, {
            props: { initData }
        }),
    }).$mount('#reviews-widget-app');
}
