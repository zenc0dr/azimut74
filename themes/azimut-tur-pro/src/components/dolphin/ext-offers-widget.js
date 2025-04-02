import Vue from 'vue';
Vue.config.productionTip = false;
import App from "./ExtOffersWidget";
new Vue({
    render: h => h(App)
}).$mount('#dolphin-ext-offers-widget-app');
