import Vue from 'vue';
Vue.config.productionTip = false;
import TopMenu from "./CallBack";
new Vue({
    render: h => h(TopMenu)
}).$mount('#call-back-app');
