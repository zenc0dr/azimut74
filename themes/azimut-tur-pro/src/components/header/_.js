import Vue from 'vue';
Vue.config.productionTip = false;
import TopMenu from "./TopMenu.vue";
new Vue({
    render: h => h(TopMenu)
}).$mount('#top-menu-app');

/*
$(document).ready(function () {
    if (location.pathname.includes('russia-river-cruises') || location.pathname === '/') {
        $('.header-bot__cruize-phone').css('opacity', 1)
        $('.footer__phones-right').css('opacity', 1)
        $('.footer .all_phones .number').css('opacity', 1)
    }
});
*/
