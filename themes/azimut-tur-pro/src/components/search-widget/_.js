import Vue from 'vue';

Vue.config.productionTip = false;
import TopMenu from "./SearchWidget";
new Vue({
    render: h => h(TopMenu)
}).$mount('#search-widget-app');


window.$ = $;