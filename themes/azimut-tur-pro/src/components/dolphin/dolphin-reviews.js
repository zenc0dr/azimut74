import Vue from 'vue';
Vue.config.productionTip = false;
import App from "./DolphinReviews";
new Vue({
    render: h => h(App)
}).$mount('#dolphin-reviews-app');
