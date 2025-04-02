import Vue from 'vue';
Vue.config.productionTip = false;
import App from "./ExtResults";
new Vue({
    render: h => h(App)
}).$mount('#dolphin-ext-results-app');
