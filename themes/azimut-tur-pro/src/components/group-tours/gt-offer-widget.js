import Vue from 'vue';
Vue.config.productionTip = false;
import App from "./gt-offer-widget.vue";
new Vue({
   render: h => h(App)
}).$mount('#gt-offer-widget-app');
