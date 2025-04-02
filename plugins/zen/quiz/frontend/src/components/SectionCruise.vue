<template>
    <div class="section-cruise__wrapper">
        <div class="container" :class="{is_modal : isModal}">
            <div class="section-cruise__bgc"></div>
            <div v-if="!is_send" class="container-inner" :class="{ process: process }">
                <div class="section-cruise__steps-progress">
                    <div v-for="(item,index) in config.max_step" class="section-cruise__step-progress">
                        <div class="section-cruise__step-number"
                             :class="{active:item === step}"
                             @click="clickControlStep(item)"
                        >{{ index + 1 }}
                        </div>
                    </div>
                </div>
                <div v-if="step === 1" class="section-cruise__step">
                    <div class="section-cruise__step-title">
                        Откуда отправление?
                    </div>
                    <div class="section-cruise__step-inputs">
                        <div class="section-cruise__inputs-inner">
                            <div class="section-cruise__step-input" :class="{ bad: alerts.city_id }">
                                <div class="section-cruise__input-label">Выберите город</div>
                                <DwarfSelect :options="config.cities" name="city_id" v-model="form.city_id"
                                             @update:modelValue="newValue => form.city_id = newValue"/>
                                <span v-if="alerts.city_id" class="bad-text">{{ alerts.city_id.text }}</span>
                            </div>
                            <div class="section-cruise__step-control">
                                <button @click="nextStep('form.city_id', 'int')">Далее</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="step === 2" class="section-cruise__step">
                    <div class="section-cruise__step-title">
                        Какой период времени Вам будет удобен?
                    </div>
                    <div class="section-cruise__step-inputs">
                        <div class="section-cruise__inputs-inner">
                            <div class="date-range zen-date-range-fix">
                                <div class="section-cruise__step-input" :class="{ bad: alerts.dates_from  }">
                                    <FlatDatepicker :label="'Выберите дату с'" v-model="form.dates.from"/>
                                    <span v-if="alerts.dates_from" class="bad-text">{{ alerts.dates_from.text }}</span>
                                </div>
                                <div class="section-cruise__step-input" :class="{ bad: alerts.dates_to }">
                                    <FlatDatepicker :label="'Выберите дату по'" v-model="form.dates.to"/>
                                    <span v-if="alerts.dates_to" class="bad-text">{{ alerts.dates_to.text }}</span>
                                </div>
                            </div>
                            <div class="section-cruise__step-control">
                                <button @click="nextStep(form.dates, 'date')">Далее</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="step === 3" class="section-cruise__step">
                    <div class="section-cruise__step-title">
                        Выберите продолжительность круиза
                    </div>
                    <div class="section-cruise__step-inputs">
                        <div class="section-cruise__inputs-inner">
                            <div class="date-range">
                                <div class="section-cruise__step-input" :class="{ bad: alerts.day_from }">
                                    <DwarfNumber v-model="form.days.from" size="medium" label="Дней от"
                                                 :min="config.days.min" :max="config.days.max"/>
                                    <span v-if="alerts.day_from" class="bad-text">{{ alerts.day_from.text }}</span>
                                </div>
                                <div class="section-cruise__step-input" :class="{ bad: alerts.day_to }">
                                    <DwarfNumber v-model="form.days.to" size="medium" label="Дней до"
                                                 :min="config.days.min" :max="config.days.max"/>
                                    <span v-if="alerts.day_to" class="bad-text">{{ alerts.day_to.text }}</span>
                                </div>
                            </div>
                            <div class="section-cruise__step-control">
                                <button @click="nextStep(form.days, 'day')">Далее</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="step === 4" class="section-cruise__step">
                    <div class="section-cruise__step-title">
                        Количество взрослых и детей
                    </div>
                    <div class="section-cruise__step-inputs">
                        <div class="section-cruise__inputs-inner">
                            <div class="date-range">
                                <div class="section-cruise__step-input" :class="{ bad: alerts.people_adult }">
                                    <DwarfNumber v-model="form.people.adult" size="medium" label="Взрослых"
                                                 :min="config.people.min" :max="config.people.max"/>
                                    <span v-if="alerts.people_adult" class="bad-text">{{
                                            alerts.people_adult.text
                                        }}</span>
                                </div>
                                <div class="section-cruise__step-input">
                                    <DwarfNumber v-model="form.people.children" size="medium" label="Детей"
                                                 :min="config.people.min" :max="config.people.max"/>
                                </div>
                            </div>
                            <div class="section-cruise__step-control">
                                <button @click="nextStep(form.people, 'people')">Далее</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="step === 5" class="section-cruise__step">
                    <div class="section-cruise__step-title">
                        Контакты для отправки круизов
                    </div>
                    <div class="section-cruise__step-inputs ">
                        <div class="section-cruise__inputs-inner column">
                            <div class="inputs-text">
                                <div class="section-cruise__step-input mb-3" :class="{ bad: alerts.phone }">
                                        <div class="section-cruise__input-label">Ваш телефон</div>
                                        <input type="text" class="email-phone__input p-inputtext" name="name"   placeholder="+7 (9__)___-__-__" v-model="form.phone"
                                               v-phone>
                                        <span v-if="alerts.phone" class="bad-text">{{ alerts.phone.text }}</span>
                                </div>
                                <div class="section-cruise__step-input mb-4" :class="{ bad: alerts.email }">
                                    <div class="section-cruise__input-label">Ваш E-mail</div>
                                    <input type="text" name="name" class="email-phone__input p-inputtext" placeholder='Например "example@mail.ru"'
                                           v-model="form.email">
                                    <span v-if="alerts.email" class="bad-text">{{ alerts.email.text }}</span>
                                </div>
                            </div>
                            <div class="section-cruise__input-agree" :class="{'bad-text':alerts.is_accept}">
                                <input
                                    type="checkbox"
                                    value="false"
                                    v-model="is_accept"
                                >
                                Нажимая кнопку «Перезвоните мне», я даю свое согласие на <a
                                href="https://xn----7sbveuzmbgd.xn--p1ai/personal-soglasie" target="_blank">обработку
                                моих персональных данных,</a> в соответствии с Федеральным законом от 27.07.2006 года
                                №152-ФЗ «О персональных данных», на условиях и для целей, определенных в Согласии на
                                обработку персональных данных, в соответствии с <a target="_blank" href="/politika-konfidencialnosti">Политикой по обработке персональных данных Компании</a> <span v-if="alerts.is_accept" class="agree-notice"> &lt;-- Не принято соглашение!!!</span>
                            </div>
                            <div class="section-cruise__step-control flex-end">
                                <button @click="nextStep([form.phone,form.email], 'string', true)">
                                    Рассчитать стоимость тура
                                </button>
                            </div>
                        </div>

                    </div>

                </div>
                <div class="section-cruise__left-steps" v-if="config.max_step !== step">
                    <template v-if="step === config.max_step-1">
                        <i class="bi bi-stopwatch-fill"></i> Остался всего 1 шаг
                    </template>
                    <template v-else>
                        <i class="bi bi-stopwatch-fill"></i> Осталось всего {{ config.max_step - step }} шага
                    </template>
                </div>
            </div>
            <div v-else class="section-cruise__sended">
                <div class="section-cruise__sended-text">
                    Спасибо за заявку, мы скоро с вами свяжемся!
                </div>
                <div class="section-cruise__sended-timer">
                    <i class="bi bi-stopwatch-fill"></i> Автоматически закроется через {{ timer }} сек.
                </div>

            </div>
        </div>
    </div>
</template>

<script>
import {get} from 'lodash'
import DwarfSelect from './dwarf/DwarfSelect.vue';
import FlatDatepicker from './FlatDatepicker.vue';
import DwarfNumber from './dwarf/DwarfNumber.vue';

export default {
    components: {
        DwarfSelect,
        FlatDatepicker,
        DwarfNumber
    },
    name: "SectionCruise",
    mounted() {
        this.checkScreenWidth();
        window.addEventListener('resize', this.checkScreenWidth);
        this.getCities();
        this.timer = this.config.close_timer
    },
    data() {
        return {
            config: {
                cities: [],
                max_step: 5,
                close_timer: 5,
                days: {
                    min: 1,
                    max: 30
                },
                people: {
                    min: 1,
                    max: 10
                },

            },
            form: {
                city_id: null,
                dates: {
                    from: null,
                    to: null
                },
                days: {
                    from: 1,
                    to: 2
                },
                people: {
                    adult: 1,
                    children: 0
                },
                phone: '',
                email: ''
            },
            timer: 0,
            alerts: {},
            step: 1,
            is_accept: false,
            process: false,
            is_send: false,
            is_mobile: false,

        }
    },
    computed: {},
    props: {
        isModal: {
            type: Boolean,
            required: false,
            default: false
        },
    },
    directives: {
        phone: {
            mounted(el) {
                let oldPhone = '';

                el.oninput = function (e) {
                    if (!e.isTrusted) {
                        return;
                    }

                    const x = this.value.replace(/\D/g, '').match(/(\d{0,1})(\d{0,3})(\d{0,3})(\d{0,2})(\d{0,2})/);
                    x[1] = '+7';
                    this.value = !x[3] ? x[1] + ' (' + x[2] : x[1] + ' (' + x[2] + ') ' + x[3] + (x[4] ? '-' + x[4] : '') + (x[5] ? '-' + x[5] : '');

                    if (oldPhone === this.value && !x[4] && !x[3]) {
                        this.value = '';
                    }

                    oldPhone = this.value;
                    el.dispatchEvent(new Event('input'));
                };
            }
        }
    },

    methods: {
        nextStep(val, type, last) {
            this.alerts = {}
            let value = get(this, val);

            if (type === 'int') {
                if (value <= 0 || value === null) {
                    let splitVal = val.split('.');
                    this.alerts[splitVal[1]] = {
                        text: 'Ничего не выбрано',
                        type: 'danger',
                        val: value
                    }
                }
            }

            if (type === 'date') {
                this.handleDate(val);
            }

            if (type === 'day') {
                this.handleDay(val);
            }

            if (type === 'people') {
                this.handlePeople(val);
            }

            if (type === 'string') {
                this.handleString(val);
            }

            if (Object.keys(this.alerts).length === 0 && !last ) {
                this.step++
            } else if (last) {
                this.send()
            }
        },

        handleDate(val) {
            if (val.to === null) {
                this.createAlert('dates_to', 'Дата ДО не указана', this.form.dates.to);
            }

            if (val.from === null) {
                this.createAlert('dates_from', 'Дата ОТ не указана', this.form.dates.from);
            }

            if (val.to !== null && val.from !== null && val.to <= val.from) {
                this.createAlert('dates_to', 'Дата ДО меньше чем дата ОТ', this.form.dates.to);
            }
        },

        handleDay(val) {
            if (val.to < 1) {
                this.createAlert('day_to', 'Кол-во дней ДО указано неверно', this.form.days.to);
            }

            if (val.from < 1) {
                this.createAlert('day_from', 'Кол-во дней ОТ указано неверно', this.form.days.from);
            }

            if (val.to !== 0 && val.from !== 0 && val.to < val.from) {
                this.createAlert('day_to', 'Дней ДО меньше чем Дней ОТ', this.form.days.to);
            }
        },

        handlePeople(val) {
            if (val.adult < 1) {
                this.createAlert('people_adult', 'Кол-во взрослых должно быть > 1', this.form.people.adult);
            }
        },

        handleString(val) {
            if (val[0] === '') {
                this.createAlert('phone', 'Поле Телефон пустое', this.form.phone);
            }
            if (val[1] === '') {
                this.createAlert('email', 'Поле Email пустое', this.form.email);
            }
        },

        createAlert(key, text, val) {
            this.alerts[key] = {
                text: text,
                type: 'danger',
                val: val
            }
        },
        send() {

            if (!this.is_accept) {
                this.createAlert('is_accept', 'Соглашение не принято', null);
                return
            }

            ym(13605125,'reachGoal','request_for_payment')

            this.process = true
            APP.api({
                url: '/zen/quiz/api/Store:push',
                data: {form: this.form},
                no_preloader: true,
                then: response => {
                    this.process = false
                    if (response.success) {
                        this.is_send = true
                        if (this.isModal) {
                            setInterval(() => {
                                this.timer--
                                if (this.timer <= 0) {
                                    clearInterval(this.timer)
                                    this.$emit('success')
                                }
                            }, 1000)
                        }
                    }
                }
            })
        },
        checkScreenWidth() {
            this.is_mobile = window.innerWidth < 768;
        },
        clickControlStep(step) {
            if (this.step !== step && (this.step + 1 === step || this.step - 1 === step)) {
                if (this.step > step) {
                    this.step = step
                }
            }
        },
        getCities() {
            APP.api({
                url: '/zen/quiz/api/Cities:getList',
                no_preloader: true,
                then: response => {
                    this.process = false
                    if (response.success) {
                        this.config.cities = response.cities
                    }
                }
            })
        }

    },
    beforeDestroy() {
        window.removeEventListener('resize', this.checkScreenWidth);
    }
}
</script>
