import Vue from 'vue';
Vue.config.productionTip = false;
import App from "./gt-search-widget.vue";
new Vue({
    render: h => h(App)
}).$mount('#gt-search-widget-app');
