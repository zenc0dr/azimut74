import Vue from 'vue';
Vue.config.productionTip = false;
import ZenGallery from "./ZenGallery";

$(document).ready(function () {
    $('body').append('<div id="zen-gallery-app"></div>')
    new Vue({
        render: h => h(ZenGallery)
    }).$mount('#zen-gallery-app');
})
