import Vue from 'vue';
Vue.config.productionTip = false;
import TinyboxGallery from "../sliders/SliderThumbs";
new Vue({
    render: h => h(TinyboxGallery)
}).$mount('#tinybox-app');
