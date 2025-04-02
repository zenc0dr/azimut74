import Vue from 'vue';
import PrimeVue from 'primevue/config';
Vue.config.productionTip = false;
Vue.use(PrimeVue);
import App from "./ScheduleTable";
new Vue({
    render: h => h(App)
}).$mount('#dolphin-schedule-table-app');
