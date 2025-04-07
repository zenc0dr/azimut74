<template>
    <div v-click-outside="closeDropdowns" class="top-menu" v-if="width">
        <MenuTree
            v-if="width > 993"
            :items="items"
            :root-classes="root_classes"
            :item-classes="item_classes"
            :carets="carets"
            :click-outside="click_outside"
        />
        <div class="top-menu_mobile bg-blue-300" v-if="width <= 992">
            <div class="container">
                <div class="py-2 d-flex">
                    <div class="col-auto top-menu_mobile-humburger">
                        <i class="bi bi-list fs-h1 text-white"
                           @click="clickHaburger()"
                           style="cursor:pointer">
                        </i>
                    </div>
                    <div class="col justify-content-center d-flex">
                        <a href="/" class="top-menu_mobile-mini-logo">
                            <img src="/themes/azimut-tur-pro/assets/images/logo-mini-trans.png" width="50px" alt="Азимут-Тур">
                        </a>
                    </div>

                    <div class="col-auto col-auto d-flex align-items-center justify-content-center">
                        <!--<div class="whats-app-btn kruiz_injector3 only-river-crs" onclick="window.open('https://wa.me/79271149850?text=Меня интересуют речные круизы')"></div>-->
                        <i class="bi bi-telephone-inbound fs-h1 text-white" @click="clickContacts()" style="cursor:pointer"></i>
                    </div>

                </div>
            </div>

            <div v-if="mobile_menu" class="top-menu__mobile-menu bg-blue-300">
                <div class="container">
                    <SliderMenu
                        :items="items"
                        :width="width"
                    />
                </div>
            </div>

            <div v-if="contacts_menu" class="top-menu__contacts-menu bg-primary-100 py-4">
                <div class="container">
                    <div v-if="!is_rivercrs" class="top-menu__contacts-menu__phones col-12 mb-3  d-flex flex-column align-items-center">
                        <div class="top-menu__contacts-menu__phone">
                            <a href="tel:88452990819" class="text-decoration-none fs-h2 c-primary-200">
                                8 (8452) 99 08 19<i style="margin-left: 15px" class="bi bi-telephone-forward"></i>
                            </a>
                        </div>
                    </div>
                    <div class="d-flex flex-column text-center">
                        <div style="display:none" class="fs-ss mb-1">Круизный отдел</div>
                        <a style="display:none" href="tel:88452990819" class="text-decoration-none c-primary-200 fs-def fw-bolder">
                            8 (8452) 99 08 19
                        </a>
                        <a href="tel:88001004959" class="text-decoration-none c-primary-200 fs-def fw-bolder">
                            8 (800) 100 49 59
                        </a>
                        <div class="fs-ss mb-1">Звонок бесплатный</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import MenuTree from "./MenuTree";
import SliderMenu from "./SliderMenu";
import ClickOutside from "./directives/ClickOutside";

export default {
    name: 'TopMenu',
    components: {MenuTree, SliderMenu},
    directives: {
        'click-outside': ClickOutside
    },
    created() {
        this.is_rivercrs = location.pathname.includes('russia-river-cruises')
        this.items = JSON.parse($('meta[name="top-menu"]').attr('content'))
        this.bindWidthDetect()
    },
    data() {
        return {
            is_rivercrs: false,
            mobile_menu: false,
            contacts_menu: false,
            //mgo_phone: null,
            width: null,
            root_classes: [
                'd-flex justify-content-left',
                'top-menu__dropdown',
            ],
            item_classes: [
                'px-4 py-3  text-white top-menu__top-level fs-s',
                'p-3 text-white top-menu__dropdown-item',
                'text-white level-3'
            ],
            carets: [
                false,
                true
            ],
            click_outside: false
        }
    },
    watch: {
        /*
        contacts_menu(active) {
            if (active) {
                this.mgo_phone = $('.mgo-number-29808').first().text()
            }
        },
         */
        width(width) {
            if (width > 992) {
                $('body').css('padding-top', 0)
            } else {
                this.mobile_menu = false
                $('body').css('padding-top', '57px')
            }
        }
    },
    methods: {
        clickHaburger() {
            this.mobile_menu = !this.mobile_menu
            this.contacts_menu = false
            this.switchShadow()
        },
        clickContacts() {
            this.contacts_menu = !this.contacts_menu
            this.mobile_menu = false
            this.switchShadow()
        },
        switchShadow() {


            if (!this.mobile_menu && !this.contacts_menu) {
                if ($('body').hasClass('shadow')) $('body').toggleClass('shadow')
            } else {
                if ($('body').hasClass('shadow') === false) $('body').toggleClass('shadow')
            }
        },
        bindWidthDetect() {
            $(document).ready(() => {
                this.setWidth()
            })

            $(window).resize(() => {
                this.setWidth()
            })
        },
        setWidth() {
            //this.width = Math.max(window.innerWidth, document.documentElement.clientWidth)
            this.width = $('body').width()

        },
        closeDropdowns() {
            this.click_outside = !this.click_outside
        }
    }
}
</script>
<style>

</style>
