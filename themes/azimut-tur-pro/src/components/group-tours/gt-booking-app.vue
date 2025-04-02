<template>
    <Modal
        :show="show"
        :max-width="1100"
        title="Бронирование тура"
        @close="$emit('close')">
        <div v-if="order !== null" class="dw-order">
            <div v-if="order.tour_name" class="dw-order-h">{{ order.tour_name }}</div>
            <div v-if="order.tarrif_name && order.tarrif_name !== order.tour_name"
                 class="dw-order-h2">
                {{ order.tarrif_name }}
            </div>
            <div class="send-form">

                <div class="send-form__panel">
                    <div>
                        <div class="send-form__input">
                            <div>Имя</div>
                            <input :class="{ badfield:isAlert('name') }"
                                   v-model="send.name"
                                   placeholder="Ваше имя"
                            />
                        </div>
                        <div class="send-form__input">
                            <div>Телефон</div>
                            <masked-input mask="\+7(111) 111-11-11"
                                  :class="{ badfield:isAlert('phone') }"
                                  v-model="send.phone" placeholder="Ваш телефон"
                            />
                        </div>
                        <div class="send-form__input">
                            <div>E-mail</div>
                            <input :class="{ badfield:isAlert('email') }"
                                   v-model="send.email"
                                   placeholder="Ваш e-mail"
                            />
                        </div>
                    </div>
                    <div>
                        <div class="send-form__textarea">
                            <textarea
                                v-model="send.desc"
                                placeholder="Укажите любую важную для вас нюансы тура или задайте интересующий вас вопрос"
                                :class="{ badfield:isAlert('desc') }"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <ConfirmModule :confirm="confirm" @change="confirm = $event">
                <span @click="agreement_show = true">Нажимая кнопку «Бронировать», я даю свое согласие на обработку моих персональных данных, в соответствии с Федеральным законом от 27.07.2006 года №152-ФЗ «О персональных данных», на условиях и для целей, определенных в Согласии на обработку персональных данных</span>
            </ConfirmModule>
            <div class="send-form__after-form">
                <span>
                    Мы гарантируем конфиденциальность введенных данных,
                    мы не используем Ваши данные для навязчивых рассылок,
                    заказ ни к чему Вас не обязывает
                </span>
            </div>
            <div class="d-flex justify-content-center">
                <button v-if="!success" @click="sendOrder" :disabled="!confirm" class="btn red py-3 px-5 mt-3">
                    Бронировать
                </button>
            </div>
            <Notifications :notifications="alerts"/>
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
    name: "GroupToursBookingApp",
    components: {Modal, MaskedInput, Notifications, ConfirmModule},
    props: {
        order: {
            Type: Object,
            default: null
        },
        show: {
            Type: Boolean,
            default: false
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
        isAlert(field_name) {
            return !!this.alerts.find(item => {
                return item.field === field_name
            })
        },
        sendOrder() {
            if (!this.confirm) {
                return
            }
            if (this.process) {
                return
            }
            this.process = true
            this.alerts = []

            let order = {
                tour_id: this.order.id,
                tour_name: this.order.name,
                name: this.send.name,
                phone: this.send.phone,
                email: this.send.email,
                desc: this.send.desc,
                price: this.order.price,
            }

            api.sync({
                url: '/zen/gt/api/order:send',
                data: {order},
                then: response => {
                    this.process = false
                    this.alerts = response.alerts
                    if (response.success) {
                        this.success = true
                    }
                }
            })
        }
    }
}
</script>
