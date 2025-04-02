<template>
    <zen-modal max-width="1000px" z-index="100500" title="Бронирование тура" :show="show" @hide="closeOrder">
        <div class="dw-order">
            <div v-if="order.tour_name" class="dw-order-h">{{ order.tour_name }}</div>
            <div v-if="order.tarrif_name" class="dw-order-h2">{{ order.tarrif_name }}</div>
            <div class="dw-order-wrap">
                <div class="dw-order-raw">
                    <div class="dw-order-block">
                        <div class="dw-order-label">Даты тура</div>
                        <div class="dw-order-dates">
                            <div>
                                <div>{{ order.date_of }}</div>
                                <span>({{ order.date_of_dow }})</span>
                            </div>
                            -
                            <div>
                                <div>{{ order.date_to }}</div>
                                <span>({{ order.date_to_dow }})</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="dw-order-raw">
                    <div class="dw-order-block">
                        <div class="dw-order-label">Продолжительность</div>
                        <div class="dw-order-text">
                            Дней: {{ order.days }} / Ночей: {{ order.days - 1 }}
                        </div>
                    </div>
                    <div class="dw-order-block">
                        <div class="dw-order-label">Человек</div>
                        <div class="dw-order-text">
                            Взрослых: {{ order.adults }}
                            <span v-if="childrensString">
                                {{ childrensString }}
                            </span>
                        </div>
                    </div>
                </div>
                <div v-if="scope == 'Ext'" class="dw-order-raw">
                    <div class="dw-order-block">
                        <div class="dw-order-label">Отель</div>
                        <div class="dw-order-text">
                            {{ order.hotel_name }}
                        </div>
                    </div>
                    <div class="dw-order-block">
                        <div class="dw-order-label">Питание</div>
                        <div class="dw-order-text">
                            {{ order.pansion }}
                        </div>
                    </div>
                </div>
                <div class="dw-order-raw">
                    <div v-if="scope == 'Ext'" class="dw-order-block">
                        <div class="dw-order-label">Размещение</div>
                        <div class="dw-order-text">
                            {{ order.room }} ({{ order.roomc }})
                        </div>
                    </div>
                    <div v-if="scope == 'Atm'" class="dw-order-block">
                        <div class="dw-order-label">Отель</div>
                        <div class="dw-order-text">
                            {{ order.hotel_name }}
                        </div>
                    </div>
                    <div class="dw-order-block">
                        <div class="dw-order-label">Цена</div>
                        <div class="dw-order-text dw-order-price">
                            {{ order.price }} руб.
                        </div>
                    </div>
                </div>
            </div>
            <div class="dw-send-wrap">
                <div class="modal__block p-r">
                    <div class="h h_txt-bold h_size-xxs">
                        Данные о пассажирах
                    </div>
                    <div class="modal__human-details bookingDataLine mt-20">
                        <div class="modal__human-details-form dataLineForm">
                            <div class="r">
                                <div class="col-45 col-xs-100">
                                    <div class="dataLineLeft">
                                        <div class="modal__human-details-line dLine">
                                            <div><span>Имя</span></div>
                                            <div><input :class="{badfield:isAlert('name')}" v-model="send.name"
                                                        placeholder="Ваше имя" class="order orderBook"></div>
                                        </div>
                                        <div class="modal__human-details-line dLine">
                                            <div><span>Телефон</span></div>
                                            <div>
                                                <masked-input mask="\+7(111) 111-11-11"
                                                              :class="{badfield:isAlert('phone')}" v-model="send.phone"
                                                              placeholder="Ваш телефон" class="order phone orderBook"/>
                                            </div>
                                        </div>
                                        <div class="modal__human-details-line dLine">
                                            <div><span>E-mail</span></div>
                                            <div><input :class="{badfield:isAlert('email')}" v-model="send.email"
                                                        placeholder="Ваш e-mail" class="order orderBook"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-55 col-xs-100 mt-xs-20">
                                    <div class="dataLineRight">
                                        <div>
                                            <textarea :class="{badfield:isAlert('desc')}" v-model="send.desc"
                                                      booking-name="desc"
                                                      placeholder="Дополнительная информация"
                                                      class="order orderBook"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <template v-if="!success">
                        <div class="modal__offer offerItem d-flex">
                            <span @click="confirm = !confirm" class="check" :class="{checked:confirm}">
                                <i aria-hidden="true" class="fa fa-check"></i>
                            </span>
                            <div @click="show_agreement=true">
                                <span class="offerLink">
                                    Нажимая кнопку «Бронировать», я даю свое согласие на обработку моих персональных данных,
                                    в соответствии с Федеральным законом от 27.07.2006 года №152-ФЗ «О персональных данных»,
                                    на условиях и для целей, определенных в Согласии на обработку персональных данных
                                </span> в соответствии с  <a target="_blank" href="/politika-konfidencialnosti">Политикой по обработке персональных данных Компании</a>
                            </div>
                        </div>
                        <div class="modal__anno pt-30 pb-30">
                            <span>Мы гарантируем конфиденциальность введенных данных, мы не используем Ваши данные для навязчивых рассылок, заказ ни к чему Вас не обязывает</span>
                        </div>
                        <div class="dw-booking-button-wrap">
                            <button @click="sendOrder" class="dw-booking-button" :disabled="!confirm">Бронировать
                            </button>
                        </div>
                        <div v-if="process" class="preloader">
                            <img :src="`${settings.domain}/plugins/zen/dolphin/assets/images/preloaders/cubesline.gif`">
                        </div>
                    </template>

                    <agreement :show="show_agreement" @close="show_agreement=false"/>

                    <zen-alerts-box :alerts="alerts"/>
                </div>
            </div>
        </div>
    </zen-modal>
</template>
<script>

import ZenAlertsBox from "../components/ZenAlertsBox";
import Agreement from "../components/Agreement";
import ZenModal from "../components/ZenModal";
import MaskedInput from "vue-masked-input";

export default {
    name: 'Order',
    components: {
        ZenAlertsBox,
        MaskedInput,
        Agreement,
        ZenModal,
    },
    props: {
        order: {
            type: Object,
            default: null
        }
    },
    data() {
        return {
            send: {
                name: null,
                phone: null,
                email: null,
                desc: null,
            },
            confirm: false,
            alerts: null,
            process: false,
            success: false,
            scope: false,
            show_agreement: false
        }
    },
    mounted() {
        if (this.settings.widgetName == 'exp') this.scope = 'Ext'
        if (this.settings.widgetName == 'atp') this.scope = 'Atm'
    },
    methods: {
        isAlert(field_name) {
            for (let i in this.alerts) {
                if (this.alerts[i].field == field_name) return true
            }
        },
        closeOrder() {
            this.$store.commit('setAtmOrder', null)
            document.body.style.overflowY = 'auto'
        },
        sendOrder() {
            if (!this.confirm) return
            if (this.process) return
            this.process = true
            this.alerts = null

            /*
            let order = null

            if (this.scope == 'Ext') {
                order = {
                    scope: 'exp',
                    tour_id: this.$store.getters.getExpTourId,
                    tour_name: this.order.tour_name,
                    source_code: this.$store.getters.getExpSourceCode,
                    name: this.send.name,
                    phone: this.send.phone,
                    email: this.send.email,
                    desc: this.send.desc,
                    date_of: this.order.date_of,
                    date_to: this.order.date_to,
                    nights: this.order.days - 1,
                    room: this.order.room,
                    roomc: this.order.roomc,
                    adults: this.order.adults,
                    childrens: this.order.children_ages,
                    pansion: this.order.pansion,
                    price: this.order.price,
                    refer: location.href
                }
            }

            if (this.scope == 'Atm') {
                order = this.$store.getters.getAtmOrder
                order.name = this.send.name
                order.phone = this.send.phone
                order.email = this.send.email
                order.desc = this.send.desc
                order.refer = location.href
            }
            */
            let order = this.$store.getters.getAtmOrder
            order.name = this.send.name
            order.phone = this.send.phone
            order.email = this.send.email
            order.desc = this.send.desc
            order.refer = location.href

            this.$store.dispatch('apiQuery', {
                url: 'order:send',
                data: {order},
                then: (response) => {
                    if (response.alerts) {
                        this.process = false
                        this.alerts = response.alerts
                        if (response.success) {
                            this.success = true
                            this.ym('booking_bus_sea')
                        }
                    }
                }
            })
        },
        ym(target)
        {
            if (typeof window['ym'] !== 'function') {
                return null;
            }
            ym(13605125,'reachGoal', target)
        }
    },
    computed: {
        show() {
            if (this.order) {
                document.body.style.overflowY = 'hidden'
                return true
            }
            document.body.style.overflowY = 'auto'
            return false
        },
        childrensString() {
            let childrens = this.order.childrens
            if (!childrens) return null

            let ages = this.order.children_ages
            ages = ages.join(' и ')
            return ', Детей: ' + childrens + ' (' + ages + ' лет)'
        },
        settings() {
            return this.$store.getters.getSettings
        }
    }
}
</script>
