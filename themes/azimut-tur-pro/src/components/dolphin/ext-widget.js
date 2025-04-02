import Vue from 'vue';
Vue.config.productionTip = false;
import App from "./ExtWidget";
new Vue({
    render: h => h(App)
}).$mount('#dolphin-ext-widget-app');
