<template>
    <Modal :show="show" @close="show = false">
        <div class="form-callback px-2 px-sm-5 py-3">
            <template v-if="!message">
                <h2 class="mb-5 text-center c-gray-300">СПЕЦИАЛИСТ ПОЗВОНИТ<br> ВАМ В БЛИЖАЙШЕЕ ВРЕМЯ</h2>
                <div class="form-callback__form d-flex flex-column">
                    <div :class="{bad:!!bad_fields['name']}"
                         class="form-callback__wrap-input mb-4 col-12 c0 fs-s fs-xxl-def">
                        <div class="form-callback__input_label fw-bolder mb-1 c-blue-300 fs-ss fs-sm-s">
                            Ваше имя
                        </div>
                        <input
                            v-model="name"
                            class="form-callback__input"
                            placeholder="Ваше имя"
                        >
                        <span v-if="!!bad_fields['name']" class="form-callback__input-error fs-s">
                        {{ bad_fields['name'] }}
                    </span>
                    </div>
                    <!--                <div :class="{bad:!!bad_fields['email']}" class="form-callback__wrap-input mb-4 col-12 c0 fs-s fs-xxl-def ">-->
                    <!--                    <div class="form-callback__input_label fw-bolder mb-1 c-blue-300  fs-ss fs-sm-s">-->
                    <!--                        Ваш e-mail-->
                    <!--                    </div>-->
                    <!--                    <input-->
                    <!--                        v-model="email"-->
                    <!--                        class="form-callback__input"-->
                    <!--                        placeholder="Ваша электронная почта"-->
                    <!--                    >-->
                    <!--                    <span v-if="!!bad_fields['email']" class="form-callback__input-error fs-s">-->
                    <!--                        {{ bad_fields['email'] }}-->
                    <!--                    </span>-->
                    <!--                </div>-->

                    <div :class="{bad:!!bad_fields['phone']}"
                         class="form-callback__wrap-input mb-4 col-12 c0 fs-s fs-xxl-def">
                        <div class="form-callback__input_label fw-bolder mb-1 c-blue-300 fs-ss fs-sm-s">
                            Ваш телефон
                        </div>
                        <input
                            v-model="phone"
                            v-phone
                            class="form-callback__input"
                            type="text" placeholder="Ваш телефон"
                        >
                        <span v-if="!!bad_fields['phone']" class="form-callback__input-error fs-s">
                        {{ bad_fields['phone'] }}
                    </span>
                    </div>

                    <div :class="{bad:!!bad_fields['captcha']}"
                         class="form-callback__wrap-input mb-4 col-12 c0 fs-s fs-xxl-def">
                        <div class="form-callback__input_label fw-bolder mb-1 c-blue-300 fs-ss fs-sm-s">
                            Введите сколько будет {{ captcha.text }}
                        </div>
                        <input
                            v-model="captcha_enter"
                            class="form-callback__input"
                            type="text" placeholder="Введите результат"
                        >
                        <span v-if="!!bad_fields['captcha']" class="form-callback__input-error fs-s">
                        {{ bad_fields['captcha'] }}
                    </span>
                    </div>

                    <div class="form-callback__wrap-input mb-4 col-12 c0 fs-s fs-xxl-def">
                        <div class="form-callback__input_label fw-bolder mb-1 c-blue-300  fs-ss fs-sm-s ">
                            Если у Ваc остались вопросы, мы с удовольствием на них ответим
                        </div>
                        <textarea
                            v-if="!!bad_fields['note']"
                            v-model="note"
                            class="form-callback__input-textarea"
                            placeholder="Вопросы и пожелания">
                    </textarea>
                        <span v-if="!!bad_fields['note']" class="form-callback__input-error fs-s">
                        {{ bad_fields['note'] }}
                    </span>
                    </div>
                    <div class="form-callback__check-wrapper mb-4 fs-s c-gray-300">
                    <span class="form-callback__check" @click="confirm = !confirm">
                        <i class="bi" :class="(confirm) ? 'bi-check-square' : 'bi-square'" aria-hidden="true"></i>
                     </span>
                        <span class="form-callback__check-text ms-2">
                            Нажимая кнопку «Перезвоните мне», я даю свое согласие на обработку моих персональных данных,
                            в соответствии с
                            <a class="form-callback__check-link c-blue-300" target="_blank" href="/personal-soglasie">Федеральным законом от 27.07.2006 года №152-ФЗ «О персональных данных»</a>,
                            на условиях и для целей, определенных в Согласии на обработку персональных данных,
                            в соответствии с  <a target="_blank" href="/politika-konfidencialnosti">Политикой по обработке персональных данных Компании</a>
                     </span>
                    </div>
                    <div class="form-callback__btn mb-4">
                        <button :disabled="!confirm" @click="sendWidthYandexId" class="py-3 px-4 fw-bolder">Перезвоните мне</button>
                    </div>

                    <div class="form-callback__bot-text fs-ss fs-sm-s text-center c-gray-200">Мы гарантируем
                        конфиденциальность
                        введенных данных
                    </div>
                </div>
            </template>
            <template v-else>
                <div class="success-message">
                    {{ this.message }}
                </div>
            </template>
        </div>
    </Modal>
</template>

<script>


import Modal from "../vue-components/Modal";
import axios from "axios";
import Vue from 'vue';

Vue.directive('phone', {
    bind(el) {
        let oldPhone = '';

        el.oninput = function (e) {
            if (!e.isTrusted) {
                return;
            }

            const x = this.value.replace(/\D/g, '').match(/(\d{0,1})(\d{0,3})(\d{0,3})(\d{0,2})(\d{0,2})/);
            x[1] = '+7';
            this.value = !x[3] ? x[1] + ' (' + x[2] : x[1] + ' (' + x[2] + ') ' + x[3] + (x[4] ? '-' + x[4] : '') + (x[5] ? '-' + x[5] : '');

            if (oldPhone == this.value && !x[4] && !x[3]) {
                this.value = '';
            }
            oldPhone = this.value;

            el.dispatchEvent(new Event('input'));
        }
    },
});

export default {
    name: "CallBack",
    components: {Modal},
    data() {
        return {
            show: false,
            alerts: [],
            oldPhone: null,
            name: null,
            //email: null,
            note: null,
            phone: null,
            confirm: false,
            process: false,
            bad_fields: {},
            message: null,
            target_source: null,
            captcha: {},
            captcha_enter: null,
            token: null,
            yandex_id: null,
        }
    },
    mounted() {
        this.bindButton('.call-back-button')
        this.bindAimListener()
        this.generateCaptcha()
        this.generateToken()
        window.CallBack = this
        if (location.hash === '#callme') {
            this.show = true
        }
    },
    methods: {
        generateToken() {
            this.sync({
                method: 'getToken',
                then: response => {
                    this.token = response.token
                }
            })
        },
        generateCaptcha() {
            let test_a = this.getRandomInt(0, 10)
            let test_c = this.getRandomInt(0, 10)
            let test_result = test_a + test_c
            this.captcha = {
                text: test_a + '+' + test_c,
                result: test_result
            }
        },
        getRandomInt(min, max) {
            min = Math.ceil(min);
            max = Math.floor(max);
            return Math.floor(Math.random() * (max - min)) + min; //Максимум не включается, минимум включается
        },
        sync(opts) {
            this.process = true
            let domain = location.origin
            let data = (opts.data) ? opts.data : null
            let api_url = domain + '/rivercrs/api/' + opts.method
            console.log(api_url, data) // todo:debug
            // Если нет данных то запрос GET
            if (!data) {
                axios.get(api_url)
                    .then((response) => {
                        this.process = false
                        opts.then(response.data)
                        console.log(response) // todo:debug
                    })
                    .catch((error) => {
                        this.process = false
                        console.log(error) // todo:debug
                    })
            }
            // Если есть data то запрос POST
            else {
                axios.post(api_url, data)
                    .then((response) => {
                        this.process = false
                        opts.then(response.data)
                        console.log(response) // todo:debug
                    })
                    .catch((error) => {
                        this.process = false
                        console.log(error) // todo:debug
                    })
            }
        },
        open() {
            this.show = true
        },
        bindButton(selector) {
            $(document).on('click', selector, () => {
                this.show = true
            })
        },
        bindAimListener() {
            $(document).on('click', '.call-back-button button.mbtn.fs-h2', () => {
                //console.log('Было нажатие в шапке')
                this.target_source = 'booking_kruiz_cap'
                this.ym('open_kruiz_cap')
            })
            $(document).on('click', '.kruiz_injector1', () => {
                //console.log('Было нажатие на кэшбек')
                this.target_source = 'booking_kruiz_injector1'
                this.ym('open_kruiz_injector1')
            })
            $(document).on('click', 'button.rcrs-landing-submit', () => {
                //console.log('Было нажатие на людей')
                this.target_source = 'booking_kruiz_injector2'
                this.ym('open_kruiz_injector2')
            })
            $(document).on('click', 'button.btn.footer__recall-btn', () => {
                //console.log('Было нажатие с подвала')
                this.target_source = 'booking_kruiz_basement'
                this.ym('open_kruiz_basement')
            })
            $(document).on('click', '.kruiz_injector3', () => {
                this.target_source = 'booking_kruiz_injector3'
                this.ym('open_kruiz_injector3')
            })
            $(document).on('click', '.whats-app-btn', () => {
                this.ym('click_whatsapp_injector3')
            })

        },
        fillBadFields() {
            this.bad_fields = {}
            for (let i in this.alerts) {
                this.bad_fields[this.alerts[i].field] = this.alerts[i].text
            }
        },
        sendWidthYandexId() {
            if (typeof ym === 'undefined') {
                console.log('Яндекс.метрика не обнаружена')
                this.send()
            } else {
                ym(13605125, 'getClientID', (clientID) => {
                    this.yandex_id = clientID
                    // this.sync({
                    //     method: 'master.api.Log.addYandexId',
                    //     data: {
                    //         yandex_id:clientID
                    //     },
                    //     then: response => {}
                    // })
                    this.send()
                })
            }
        },
        send() {
            if (this.process || this.token === null) { // Антиклик
                return
            }

            if (this.captcha.result !== parseInt(this.captcha_enter)) {
                this.alerts = [
                    {
                        field: "captcha",
                        text: "Ответ не правильный",
                        type: "danger"
                    }
                ]
                this.fillBadFields()
                this.generateCaptcha()
                return;
            }

            this.sync({
                method: 'callback',
                data: {
                    name: this.name,
                    //email: this.email,
                    phone: this.phone,
                    note: this.note,
                    refer: location.href,
                    token: this.token,
                    yandex_id: this.yandex_id
                },
                then: (response) => {
                    if (!response.success) {
                        this.alerts = response.alerts
                        this.fillBadFields()
                        return
                    }
                    if (response.success) {
                        this.ym(this.target_source)
                        this.alerts = {}
                        this.message = response.message
                    }
                }
            })
        },
        ym(target) {
            if (typeof window['ym'] !== 'function') {
                return null;
            }
            ym(13605125, 'reachGoal', target)
        }
    }

}
</script>
