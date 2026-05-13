import Vue from 'vue';
import PrimeVue from 'primevue/config';
import 'primevue/resources/themes/saga-blue/theme.css';
import 'primevue/resources/primevue.min.css';
import 'primeicons/primeicons.css';
import ReviewsWidget from './ReviewsWidget.vue';

Vue.config.productionTip = false;
Vue.use(PrimeVue);

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
