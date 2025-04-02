import Vue from 'vue';
Vue.config.productionTip = false;
import TopMenu from "./ShipSearch";
new Vue({
    render: h => h(TopMenu)
}).$mount('#ship-search-app');





