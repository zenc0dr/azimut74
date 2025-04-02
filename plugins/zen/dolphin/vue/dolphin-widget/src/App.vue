<template>
    <div id="DolphinWidget">
        <!-- Подгрузка стилей сайта для режима serve -->
        <link v-if="settings.env === 'dev'" rel="stylesheet"
              :href="settings.domain + '/zen/dolphin/api/service:frontendCss'">
        <link rel="stylesheet"
              :href="settings.domain + '/plugins/zen/dolphin/vue/dolphin-widget/src/assets/css/widget.css'">
        <AtmSearch/>
        <Order v-if="order" :order="order"/>
        <ErrorWindow/>
    </div>
</template>
<script>
import ErrorWindow from "./components/ErrorWindow";
import AtmSearch from "./widgets/AtmSearch";
import Order from "./widgets/Order";
import $ from 'jquery'

export default {
    name: 'app',
    components: {
        ErrorWindow,
        AtmSearch,
        Order,
    },
    mounted() {
        /* Фиксация ширины экрана */
        this.eventsInstaller()
        this.$store.dispatch('initSettings')
    },
    computed: {
        settings() {
            return this.$store.getters.getSettings
        },
        order() {
            let atm_order = this.$store.getters.getAtmOrder
            if (atm_order) {
                return atm_order
            }
        }
    },
    methods: {
        eventsInstaller() {
            $(document).ready(() => {
                this.onResizeWindow()
            });

            $(window).resize(() => {
                this.onResizeWindow()
            });
        },
        onResizeWindow() {
            let width = document.documentElement.clientWidth
            this.$store.commit('setWidth', width)
        }
    }
}
</script>
<style>
@import '~vue2-datepicker/index.css';
@import '~font-awesome/css/font-awesome.min.css';
</style>
