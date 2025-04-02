<template>
    <Modal
        :show="order !== null"
        :max-width="1100"
        title="Бронирование тура"
        @close="$emit('close')">
        <div v-if="order !== null" class="dw-order">
            <div v-if="order.tour_name" class="dw-order-h">{{ order.tour_name }}</div>
            <div v-if="order.tarrif_name && order.tarrif_name !== order.tour_name"
                 class="dw-order-h2">
                {{ order.tarrif_name }}
            </div>
            <div class="dw-order-wrap">
                <div class="dw-order-raw">
                    <div class="dw-order-block">
                        <div class="dw-order-label">Даты тура</div>
                        <div class="dw-order-dates">
                            <div>
                                <div>{{ order.date_of }}</div><span>({{ order.date_of_dow }})</span>
                            </div>
                            -
                            <div>
                                <div>{{ order.date_to }}</div><span>({{ order.date_to_dow }})</span>
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
                            <span v-if="childrensString()">
                                {{ childrensString() }}
                            </span>
                        </div>
                    </div>
                </div>
                <div v-if="order.scope === 'ext'" class="dw-order-raw">
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
                    <div v-if="order.scope === 'ext'" class="dw-order-block">
                        <div class="dw-order-label">Размещение</div>
                        <div class="dw-order-text">
                            {{ order.room }} ({{ order.roomc }})
                        </div>
                    </div>
                    <div v-if="order.scope === 'atm'" class="dw-order-block">
                        <div class="dw-order-label">Отель</div>
                        <div class="dw-order-text">
                            {{ order.hotel_name }}
                        </div>
                    </div>
                    <div class="dw-order-block">
                        <div class="dw-order-label">Цена</div>
                        <div class="dw-order-text dw-order-price">
                            {{ order.price.sum }} руб.
                        </div>
                    </div>
                </div>
            </div>
            <div class="send-form">
                <div class="send-form__title">Данные о пассажирах</div>
                <div class="send-form__panel">
                    <div>
                        <div class="send-form__input">
                            <div>Имя</div>
                            <input :class="{badfield:isAlert('name')}"
                                   v-model="send.name"
                                   placeholder="Ваше имя"
                            />
                        </div>
                        <div class="send-form__input">
                            <div>Телефон</div>
                            <masked-input mask="\+7(111) 111-11-11"
                                  :class="{badfield:isAlert('phone')}"
                                  v-model="send.phone" placeholder="Ваш телефон"
                            />
                        </div>
                        <div class="send-form__input">
                            <div>E-mail</div>
                            <input :class="{badfield:isAlert('email')}"
                                   v-model="send.email"
                                   placeholder="Ваш e-mail"
                            />
                        </div>
                    </div>
                    <div>
                        <div class="send-form__textarea">
                            <textarea
                                v-model="send.desc"
                                placeholder="Дополнительная информация"
                                :class="{badfield:isAlert('desc')}"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <ConfirmModule :confirm="confirm" @change="confirm = $event">
                <span @click="agreement_show = true">
                    Нажимая кнопку «Бронировать», я даю свое согласие на обработку моих персональных данных,
                    в соответствии с Федеральным законом от 27.07.2006 года №152-ФЗ «О персональных данных»,
                    на условиях и для целей, определенных в Согласии на обработку персональных данных
                </span>, в соответствии с  <a target="_blank" href="/politika-konfidencialnosti">Политикой по обработке персональных данных Компании</a>
            </ConfirmModule>
            <div class="dw-order__desc">
                <span>
                    Мы гарантируем конфиденциальность введенных данных,
                    мы не используем Ваши данные для навязчивых рассылок,
                    заказ ни к чему Вас не обязывает
                </span>
            </div>
            <div class="d-flex justify-content-center">
                <button v-if="!success" @click="sendOrder" :disabled="!confirm" class="btn red py-3 px-5 mt-3">Бронировать</button>
            </div>
            <Notifications :notifications="alerts" />
            <Modal :show="agreement_html && agreement_show" @close="agreement_show = false">
                <div class="p-4" v-html="agreement_html"></div>
            </Modal>
        </div>
    </Modal>
</template>

<script>
import api from '../vue-components/api';
import Modal from "../vue-components/Modal";
import MaskedInput from "vue-masked-input";
import Notifications from "../vue-components/Notifications";
import ConfirmModule from "../vue-components/ConfirmModule";
export default {
    name: "BookingApp",
    components: { Modal, MaskedInput, Notifications, ConfirmModule },
    props: {
        order: {
            Type: Object,
            default: null
        },
        scope: {
            Type: String,
            default: null
        }
    },
    data() {
        return {
            process: false,
            send: {
                name: null,
                phone: null,
                email: null,
                desc: null
            },
            confirm: false,
            success: false, // Если всё отправлено
            alerts: [],
            agreement_html: null,
            agreement_show: false,
        }
    },
    mounted() {
        api.sync({
            url: '/zen/dolphin/api/service:getAgreement',
            then: response => {
                this.agreement_html = response.html
            }
        })
    },
    methods: {
        isAlert(field_name)
        {
            return !!this.alerts.find(item => {
                return item.field === field_name
            })
        },
        childrensString()
        {
            let ages = this.order.childrens
            if (!ages || !ages.length) {
                return
            }
            ages = ages.join(' и ')
            return ', Детей: ' + ages.length + ' (' + ages + ' лет)'
        },
        ym(target)
        {
            if (typeof window['ym'] !== 'function') {
                return null;
            }
            ym(13605125,'reachGoal', target)
        },
        sendOrder()
        {
            if (!this.confirm) {
                return
            }
            if (this.process) {
                return
            }
            this.process = true
            this.alerts = []

            let order = null

            if (this.scope === 'ext') {
                order = {
                    scope: 'ext',
                    tour_id: this.order.tour_id,
                    tour_name: this.order.tour_name,
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
                    childrens: this.order.childrens,
                    pansion: this.order.pansion,
                    price: this.order.price.sum,
                    refer: location.href
                }
            }

            if (this.scope === 'atm') {
                // ???
            }

            api.sync({
                url: '/zen/dolphin/api/order:send',
                data: {order},
                then: response => {
                    this.process = false
                    this.alerts = response.alerts
                    if (response.success) {
                        this.success = true
                        this.ym('booking_excursion_tour')
                    }
                }
            })
        }
    }
}
</script>
