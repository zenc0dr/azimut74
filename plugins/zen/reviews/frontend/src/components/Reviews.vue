<template>
    <div class="reviews-wrapper">
        <div class="container">
            <div v-if="!is_send" class="reviews-form" :class="{ process: process }">
                <div class="reviews-form__title">
                    Какие впечатления у Вас остались от отдыха на теплоходе?
                </div>
                <div class="reviews-form__input" :class="{ bad: alerts.name }">
                    <label for="name">Введите Ваше имя</label>
                    <input type="text" name="name" :placeholder="(is_mobile) ? config.name.pl_mb : config.name.pl_pc"
                           v-model="form.name">
                    <span v-if="alerts.name" class="bad-text">{{ alerts.name.text }}</span>
                </div>
                <div class="reviews-form__input reviews-form__input-select" :class="{ bad: alerts.ship_id }">
                    <label for="ship_id">Выберите теплоход на котором отдыхали</label>
                    <DwarfSelect :options="shipssortedOptions" v-model="form.ship_id"/>
                    <span v-if="alerts.ship_id" class="bad-text">{{ alerts.ship_id.text }}</span>
                </div>
                <div class="reviews-form__input reviews-form__input-select" :class="{ bad: alerts.trip_date }">
                    <label for="trip_date">Когда Вы ездили?</label>
                    <DwarfSelect :options="config.dates" v-model="form.trip_date"/>
                    <span v-if="alerts.trip_date" class="bad-text">{{ alerts.trip_date.text }}</span>
                </div>
                <div class="reviews-form__input reviews-form__input-radio">
                    <label for="exp_rest">Сколько раз Вы ранее отдыхали на теплоходе (любом)?</label>
                    <div class="reviews-form__input-radio--btns">
                        <button :class="{ active: form.exp_rest === 1 }" @click="form.exp_rest = 1">Первый раз</button>
                        <button :class="{ active: form.exp_rest === 2 }" @click="form.exp_rest = 2">Второй раз</button>
                        <button :class="{ active: form.exp_rest === 3 }" @click="form.exp_rest = 3">Три и более</button>
                    </div>
                </div>
                <div class="reviews-form__input reviews-form__input-group">
                    <label for="reviews">Как бы Вы оценили?</label>
                    <div class="reviews-form__input-group--wrap"
                         :class="{ 'blur': window_help.activeElement && window_help.activeElement !== $refs.cabin }"
                         ref="cabin">
                        <div class="reviews-from__custom-name">Каюту <span @click="showHelp($event);">
                                <img src="/plugins/zen/reviews/frontend/assets/images/question-icon.svg" alt="question">
                            </span>
                        </div>
                        <CircleRating :max-rating="5" v-model="form.reviews.cabin"></CircleRating>
                        <div class="help-window"
                             :class="{ 'show': window_help.activeElement && window_help.activeElement == $refs.cabin }"
                             ref="cabin">
                            <span>Постарайтесь дать общую оценку каюте исходя из: расположения, чистоты в каюте, меблировки, исправности оборудования и прочих нюансов</span>
                            <span class="help-window__close" @click="closeHelp();">
                                <img src="/plugins/zen/reviews/frontend/assets/images/close-icon.svg" alt="close">
                            </span>
                            <!--<span
                                class="help-window__timer">подсказка исчезнет через {{ this.window_help.timer }} сек.</span>-->
                        </div>
                    </div>
                    <div class="reviews-form__input-group--wrap"
                         :class="{ 'blur': window_help.activeElement && window_help.activeElement !== $refs.food }"
                         ref="food">
                        <div class="reviews-from__custom-name">Питание <span @click="showHelp($event);">
                            <img src="/plugins/zen/reviews/frontend/assets/images/question-icon.svg" alt="question">
                        </span>
                        </div>
                        <CircleRating :max-rating="5" v-model="form.reviews.food"></CircleRating>
                        <div class="help-window"
                             :class="{ 'show': window_help.activeElement && window_help.activeElement == $refs.food }"
                             ref="food">
                            <span>Постарайтесь дать общую оценку питанию исходя из: ассортимента, вкусовых качеств и тд</span>
                            <span class="help-window__close" @click="closeHelp();">
                                <img src="/plugins/zen/reviews/frontend/assets/images/close-icon.svg" alt="close">
                            </span>
                            <!--<span
                                class="help-window__timer">подсказка исчезнет через {{ this.window_help.timer }} сек.</span>-->
                        </div>
                    </div>
                    <div class="reviews-form__input-group--wrap"
                         :class="{ 'blur': window_help.activeElement && window_help.activeElement !== $refs.service }"
                         ref="service">
                        <div class="reviews-from__custom-name">Обслуживание <span @click="showHelp($event);">
                            <img src="/plugins/zen/reviews/frontend/assets/images/question-icon.svg" alt="question">
                        </span>
                        </div>
                        <CircleRating :max-rating="5" v-model="form.reviews.service"
                                      @update:modelValue="newValue => form.reviews.service = newValue"></CircleRating>
                        <div class="help-window"
                             :class="{ 'show': window_help.activeElement && window_help.activeElement == $refs.service }"
                             ref="service">
                            <span>Постарайтесь дать общую оценку обслуживанию на борту исходя из: приветливости, дружелюбия персонала, готовности исправить поломки/накладки, разнообразия доп.услуг и тд</span>
                            <span class="help-window__close" @click="closeHelp();">
                                <img src="/plugins/zen/reviews/frontend/assets/images/close-icon.svg" alt="close">
                            </span>
                            <!--<span
                                class="help-window__timer">подсказка исчезнет через {{ this.window_help.timer }} сек.</span>-->
                        </div>
                    </div>
                    <div class="reviews-form__input-group--wrap"
                         :class="{ 'blur': window_help.activeElement && window_help.activeElement !== $refs.tours }"
                         ref="tours">
                        <div class="reviews-from__custom-name">Экскурсии <span @click="showHelp($event);">
                            <img src="/plugins/zen/reviews/frontend/assets/images/question-icon.svg" alt="question">
                        </span>
                        </div>
                        <CircleRating :max-rating="5" v-model="form.reviews.tours"></CircleRating>
                        <div class="help-window"
                             :class="{ 'show': window_help.activeElement && window_help.activeElement == $refs.tours }"
                             ref="tours">
                            <span>Постарайтесь дать общую оценку экскурсионному обслуживанию в городах остановок исходя из: насыщенности, качества экскурсий, удобства и качества транспорта, общей организации и тд</span>
                            <span class="help-window__close" @click="closeHelp();">
                                <img src="/plugins/zen/reviews/frontend/assets/images/close-icon.svg" alt="close">
                            </span>
                            <!--<span
                                class="help-window__timer">подсказка исчезнет через {{ this.window_help.timer }} сек.</span>-->
                        </div>
                    </div>
                    <div class="reviews-form__input-group--wrap"
                         :class="{ 'blur': window_help.activeElement && window_help.activeElement !== $refs.animate }"
                         ref="animate">
                        <div class="reviews-from__custom-name">Анимацию на борту <span
                            @click="showHelp($event);">
                            <img src="/plugins/zen/reviews/frontend/assets/images/question-icon.svg" alt="question">
                            </span></div>
                        <CircleRating :max-rating="5" v-model="form.reviews.anim_on_board"></CircleRating>
                        <div class="help-window"
                             :class="{ 'show': window_help.activeElement && window_help.activeElement == $refs.animate }"
                             ref="animate">
                            <span>Постарайтесь дать общую оценку развлекательной программе на борту теплохода исходя из:
                            разнообразия, насыщенности и тд</span>
                            <span class="help-window__close" @click="closeHelp();">
                                <img src="/plugins/zen/reviews/frontend/assets/images/close-icon.svg" alt="close">
                            </span>
                            <!--<span
                                class="help-window__timer">подсказка исчезнет через {{ this.window_help.timer }} сек.</span>-->
                        </div>
                    </div>
                    <div class="reviews-form__input-group--wrap"
                         :class="{ 'blur': window_help.activeElement && window_help.activeElement !== $refs.ship }"
                         ref="ship">
                        <div class="reviews-from__custom-name">Теплоход <span @click="showHelp($event);">
                            <img src="/plugins/zen/reviews/frontend/assets/images/question-icon.svg" alt="question">
                        </span>
                        </div>
                        <CircleRating :max-rating="5" v-model="form.reviews.ship"></CircleRating>
                        <div class="help-window"
                             :class="{ 'show': window_help.activeElement && window_help.activeElement == $refs.ship }"
                             ref="ship">
                            <span>Постарайтесь дать общую оценку теплоходу исходя из: общего технического состояния помещений, оборудования, интерьера и тд</span>
                            <span class="help-window__close" @click="closeHelp();">
                                <img src="/plugins/zen/reviews/frontend/assets/images/close-icon.svg" alt="close">
                            </span>
                            <!--<span
                                class="help-window__timer">подсказка исчезнет через {{ this.window_help.timer }} сек.</span>-->
                        </div>
                    </div>
                </div>
                <div class="reviews-form__input reviews-form__input-textarea" :class="{bad:alerts.reviews_text}">
                    <TextareaCounter
                        v-model="form.reviews_text" label="Напишите отзыв"
                    ></TextareaCounter>
                    <span v-if="alerts.reviews_text" class="bad-text">{{ alerts.reviews_text.text }}</span>
                    <a class="d-flex d-md-none textarea-btn__help" @click="show_modal=true">
                        <svg viewBox="0 0 25 24" width="20px" height="20px" class="d Vb UmNoP">
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                  d="M12.433 1.782a.75.75 0 01.75.75v1a.75.75 0 01-1.5 0v-1a.75.75 0 01.75-.75zm7.245 3.286a.75.75 0 010 1.06l-.707.708a.75.75 0 01-1.06-1.061l.707-.707a.75.75 0 011.06 0zm-14.446.035a.75.75 0 011.06 0L7 5.81a.75.75 0 01-1.06 1.061l-.708-.707a.75.75 0 010-1.06zm5.295 2.865C9.103 9.115 8.82 10.486 9.03 11.79c.219 1.362.982 2.645 1.624 3.399l.18.21v1.292h4.05v-1.238l.122-.186c.903-1.39 1.453-2.897 1.489-4.211.034-1.284-.414-2.357-1.505-3.067-1.796-1.168-3.716-.51-4.463-.022zm-.862-1.23c1.015-.68 3.638-1.635 6.143-.005 1.609 1.047 2.232 2.663 2.186 4.364-.043 1.602-.672 3.298-1.61 4.798v2.297h-7.05v-2.247c-.724-.915-1.532-2.34-1.785-3.917-.282-1.76.135-3.713 2.067-5.253l.024-.02.025-.016zm-5.806 6.03a.75.75 0 01.75-.75h1a.75.75 0 010 1.5h-1a.75.75 0 01-.75-.75zm14.913 0a.75.75 0 01.75-.75h1a.75.75 0 110 1.5h-1a.75.75 0 01-.75-.75zm-7.928 7.342v.608h4.035v-.608h-4.035zm-1.5-.65c0-.47.38-.85.85-.85h5.335c.47 0 .85.38.85.85v1.846a.983.983 0 01-.308.7.808.808 0 01-.542.212h-5.335a.808.808 0 01-.543-.212.984.984 0 01-.308-.7V19.46z"></path>
                        </svg>
                        Советы по написанию отзывов
                    </a>
                    <Modal title="Что важнее всего в отзывах?"
                           :show="show_modal"
                           @close="show_modal=false"
                    >
                        <div class="textarea-help modal">
                            <div class="textarea-help__line">
                                <div class="textarea-help__subtitle">
                                    <svg viewBox="0 0 24 24" width="20px" height="20px" class="d Vb UmNoP">
                                        <path
                                            d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm-2 15.2L5.8 13l1.6-1.6L10 14l6.8-6.8 1.6 1.6-8.4 8.4z"></path>
                                    </svg>
                                    Что будет полезным:
                                </div>
                                <ul>
                                    <li>Будьте точны: чем больше деталей, тем лучше</li>
                                    <li>Расскажите, что было замечательно, что — плохо, а что — вполне приемлемо</li>
                                    <li>Расскажите нам то, что рассказали бы своим друзьям</li>
                                    <li>Добавьте несколько советов и рекомендаций</li>
                                </ul>
                            </div>
                            <div class="textarea-help__line">
                                <div class="textarea-help__subtitle">
                                    <svg viewBox="0 0 24 24" width="20px" height="20px" class="d Vb UmNoP">
                                        <path
                                            d="M4.929 4.929c-3.904 3.905-3.904 10.237 0 14.142 3.905 3.905 10.238 3.905 14.143.001 3.904-3.904 3.904-10.238-.001-14.143-3.905-3.905-10.238-3.905-14.142 0zm10.844 4.479l-2.476 2.476 2.476 2.475-1.415 1.415-2.475-2.476-2.475 2.476-1.414-1.414 2.475-2.475-2.474-2.476 1.414-1.414 2.476 2.476 2.476-2.476 1.412 1.413z"></path>
                                    </svg>
                                    Чего избегать:
                                </div>
                                <ul>
                                    <li>Нецензурной лексики, угроз или личных оскорблений</li>
                                    <li>Упоминаний персональной информации (эл. адреса и номера телефонов)</li>
                                    <li>Использования ИСКЛЮЧИТЕЛЬНО ЗАГЛАВНЫХ БУКВ</li>
                                    <li>Рассказов о чужом опыте и мнениях</li>
                                </ul>
                            </div>
                        </div>
                    </Modal>
                    <div class="textarea-help">
                        <div class="textarea-help__title">Что важнее всего в отзывах?</div>
                        <div class="textarea-help__line">
                            <div class="textarea-help__subtitle">
                                <svg viewBox="0 0 24 24" width="20px" height="20px" class="d Vb UmNoP">
                                    <path
                                        d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm-2 15.2L5.8 13l1.6-1.6L10 14l6.8-6.8 1.6 1.6-8.4 8.4z"></path>
                                </svg>
                                Что будет полезным:
                            </div>
                            <ul>
                                <li>Будьте точны: чем больше деталей, тем лучше</li>
                                <li>Расскажите, что было замечательно, что — плохо, а что — вполне приемлемо</li>
                                <li>Расскажите нам то, что рассказали бы своим друзьям</li>
                                <li>Добавьте несколько советов и рекомендаций</li>
                            </ul>
                        </div>
                        <div class="textarea-help__line">
                            <div class="textarea-help__subtitle">
                                <svg viewBox="0 0 24 24" width="20px" height="20px" class="d Vb UmNoP">
                                    <path
                                        d="M4.929 4.929c-3.904 3.905-3.904 10.237 0 14.142 3.905 3.905 10.238 3.905 14.143.001 3.904-3.904 3.904-10.238-.001-14.143-3.905-3.905-10.238-3.905-14.142 0zm10.844 4.479l-2.476 2.476 2.476 2.475-1.415 1.415-2.475-2.476-2.475 2.476-1.414-1.414 2.475-2.475-2.474-2.476 1.414-1.414 2.476 2.476 2.476-2.476 1.412 1.413z"></path>
                                </svg>
                                Чего избегать:
                            </div>
                            <ul>
                                <li>Нецензурной лексики, угроз или личных оскорблений</li>
                                <li>Упоминаний персональной информации (эл. адреса и номера телефонов)</li>
                                <li>Использования ИСКЛЮЧИТЕЛЬНО ЗАГЛАВНЫХ БУКВ</li>
                                <li>Рассказов о чужом опыте и мнениях</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="reviews-form__input reviews-form__input-group">
                    <label>Как бы Вы оценили свой отдых в целом?</label>
                    <div class="reviews-form__input-group--wrap">
                        <CircleRating :max-rating="5" v-model="form.reviews.cruise"></CircleRating>
                    </div>
                </div>
                <div class="reviews-form__input reviews-form__input-group">
                    <label>Как бы Вы оценили работу компании Азимут?</label>
                    <div class="reviews-form__input-group--wrap">
                        <CircleRating :max-rating="5" v-model="form.reviews.azimut"></CircleRating>
                    </div>
                </div>

                <div class="reviews-form__input reviews-form__input-group">
                    <label v-if="form.photos">Добавить фотографии (это не обязательно) ({{ form.photos.length }} из 10)</label>
                    <ImagesUploader
                        :modelValue="form.photos"
                        @update:modelValue="form.photos = $event"
                        @in_process="upload_images_process = $event"
                    />
                </div>

                <div class="reviews-form__button">
                    <button @click="send()">Отправить отзыв</button>
                </div>
            </div>
            <div v-else class="reviews-success">
                <div class="reviews-thanks">
                    <img src="/plugins/zen/reviews/frontend/assets/images/salut.svg">
                    <br>
                    <span>Спасибо за Ваш отзыв!</span>
                    <br>
                    <a href="/russia-river-cruises">Перейти к расписанию круизов</a>
                </div>
            </div>

            <div v-if="false" class="reviews-success">
                <span>Спасибо за Ваш отзыв!</span>
                <template v-if="!phone_send">
                    <span class="reviews-success__text">
                        Вознаграждение за отзыв мы отправим Вам на банковскую карту.
                        Для этого введите номер телефона, привязанный к карте
                    </span>
                    <div class="reviews-success__phone">
                        +7 <input type="text" v-model="phone" maxlength="10">
                    </div>
                    <div @click="getReward" v-if="phone && phone.length === 10" class="reviews-success__get-reward">
                        Получить вознаграждение
                    </div>
                </template>
                <template v-else>
                    ***
                    <span class="reviews-success__text">
                        Мы перечислим вознаграждение в ближайшее время!
                    </span>
                    ***
                    <template v-if="!email_send">
                        <span class="reviews-success__text">
                        Мы дарим Вам 5000 бонусных рублей на Ваш речной круиз 2024 года.
                        Чтобы получить купон, введите адрес Вашего электронного ящика
                    </span>
                        <div v-if="!email_send" class="reviews-success__email">
                            email: <input type="text" v-model="email">
                        </div>
                        <div v-if="email_test" @click="getReward2" class="reviews-success__get-reward">
                            Получить 5000 бонусных рублей
                        </div>
                    </template>
                    <div v-else class="reviews-success__text">
                        Купон на 5000 бонусных рублей отправлен на указанный адрес ({{ email }})
                    </div>
                    <div class="reviews-success__main-link">
                        <a href="/russia-river-cruises">Перейти к расписанию круизов</a>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<script>
import CircleRating from './CircleRating.vue';
import TextareaCounter from './TextareaCounter.vue';
import Modal from './Modal.vue';
import DwarfSelect from './DwarfSelect.vue';
import ImagesUploader from "./ImagesUploader";

export default {
    components: {
        CircleRating,
        ImagesUploader,
        TextareaCounter,
        Modal,
        DwarfSelect,
    },
    name: "Reviews",
    data() {
        return {
            config: {
                name: {
                    pl_pc: 'Напишите Ваше имя в любом удобном для Вас формате',
                    pl_mb: 'Напишите Ваше имя в любом формате'
                },
                help_timer: 6,
                ships: [],
                dates: [],
            },
            review_id: null,
            phone: null,
            phone_send: false, // false
            email: null,
            email_send: false,
            form: {
                name: '',
                ship_id: null,
                trip_date: '', // дата поездки
                exp_rest: 1, // сколько ранее отдыхали
                reviews: {
                    cabin: 3,         // оценка каюты
                    food: 3,          // оценка питания
                    service: 3,       // оценка обслуживания
                    tours: 3,         // оценка экскурсии
                    anim_on_board: 3, // оценка анимации на борту
                    ship: 3,          // оценка теплохода
                    azimut: 3,        // оценка Азимута
                    cruise: 3,        // оценка отдыха в целом
                },
                reviews_text: '',
                photos: []
            },
            window_help: {
                text: '',
                timer: null,
                activeElement: null
            },
            process: false,
            upload_images_process: false,
            is_send: false,
            is_mobile: false,
            show_modal: false,
            textarea_hover: false,
            alerts: {}
        };
    },
    mounted() {
        this.checkEmail()
        this.checkScreenWidth();
        window.addEventListener('resize', this.checkScreenWidth);
        this.setShips();
        this.generateMonths();
        this.takeLeadId()
    },
    beforeDestroy() {
        window.removeEventListener('resize', this.checkScreenWidth);
    },
    computed: {
        email_test()
        {
            if (!this.email) {
                return false
            }
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.email)
        },
        shipssortedOptions() {
            return this.config.ships.slice().sort((a, b) => {
                // Используйте поле 'name' для сравнения элементов
                const nameA = a.name.toUpperCase();
                const nameB = b.name.toUpperCase();

                if (/^[А-Яа-яЁё]/.test(nameA) && /^[A-Za-z]/.test(nameB)) {
                    return -1; // a - русское слово, b - английское слово
                }
                if (/^[A-Za-z]/.test(nameA) && /^[А-Яа-яЁё]/.test(nameB)) {
                    return 1; // a - английское слово, b - русское слово
                }

                return nameA.localeCompare(nameB);
            });
        }
    },
    watch: {
        phone(phone){
            this.phone = phone.replace(/\D+/, '')
        }
    },
    methods: {
        takeLeadId() {
            const params = new URLSearchParams(window.location.search)
            const lead_id = params.get('lead_id')
            if (lead_id) {
                localStorage.setItem('reviews_lead_id', lead_id)
            }
        },
        checkEmail() {
            const metaTag = document.querySelector('meta[name="review-email"]')
            if (metaTag) {
                let email = metaTag.getAttribute('content')
                if (email) {
                    this.email = email
                    this.email_send = true
                }
            }
        },
        send() {
            if (this.upload_images_process) {
                return
            }

            const lead_id = localStorage.getItem('reviews_lead_id')

            if (lead_id) {
                this.form.lead_id = lead_id
            }

            this.process = true;
            this.alerts = {};
            ReviewsApp.api({
                url: '/zen/reviews/api/Store:push',
                data: {form: this.form},
                then: response => {
                    this.process = false;
                    if (response.success) {
                        this.is_send = true;
                        this.review_id = parseInt(response.review_id)
                        let reviewsWrapper = document.querySelector('.reviews-wrapper');
                        reviewsWrapper.scrollIntoView({behavior: 'smooth'});
                    } else {
                        for (let i = 0; i < response.alerts.length; i++) {
                            let tempAlert = response.alerts[i];
                            this.alerts[tempAlert.field] = {
                                type: tempAlert.type,
                                text: tempAlert.text,
                                val: this.form[tempAlert.field]
                            };
                        }
                        setTimeout(() => {
                            let reviewsWrapper = document.querySelector('.reviews-wrapper .bad');
                            reviewsWrapper.scrollIntoView({behavior: 'smooth'});
                            let {top} = reviewsWrapper.getBoundingClientRect();
                            window.scrollBy(0, top - 100);
                        }, 300);
                    }
                }
            });
        },
        getReward() {
            ReviewsApp.api({
                url: '/zen/reviews/api/Store:sendPhone',
                data: {
                    review_id: this.review_id,
                    phone: this.phone,
                    email: this.email,
                },
                then: response => {
                    if (response.success) {
                        this.phone_send = true
                    }
                }
            })
        },
        getReward2()
        {
            ReviewsApp.api({
                url: '/zen/reviews/api/Store:sendEmail',
                data: {
                    review_id: this.review_id,
                    email: this.email
                },
                then: response => {
                    if (response.success) {
                        this.email_send = true
                    }
                }
            })
        },
        checkScreenWidth() {
            this.is_mobile = window.innerWidth < 768;
        },
        generateMonths() {
            let date = new Date();
            let options = [];

            let currentMonth = date.getMonth() + 1;
            let currentYear = date.getFullYear();
            let currentMonthName = this.getMonthName(currentMonth);

            options.push({
                id: `${currentYear}-${currentMonth < 10 ? '0' + currentMonth : currentMonth}`,
                name: `${currentMonthName} ${currentYear}`
            });

            // Создаем опции для предыдущих месяцев до января 2000 года
            while (date > new Date('Mart 1, 2000')) {
                date.setMonth(date.getMonth() - 1);

                let month = date.getMonth() + 1;
                let year = date.getFullYear();
                let monthName = this.getMonthName(month);

                options.unshift({
                    id: `${year}-${month < 10 ? '0' + month : month}`,
                    name: `${monthName} ${year}`
                });
            }

            this.config.dates = options.reverse();
        },
        getMonthName(monthNumber) {
            let monthNames = [
                'январь', 'февраль', 'март', 'апрель', 'май', 'июнь',
                'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь'
            ];
            return monthNames[monthNumber - 1];
        },
        setShips() {
            ReviewsApp.api({
                url: '/zen/reviews/api/Ships:getList',
                then: response => {
                    if (response.success) {
                        this.config.ships = response.ships;
                    }
                }
            });
        },
        showHelp(event) {
            let parentEl = event.target.closest('.reviews-form__input-group--wrap');
            this.window_help.activeElement = this.window_help.activeElement === parentEl ? null : parentEl;
            //if (this.window_help.activeElement) {
            //    clearTimeout(this.window_help.timer);
            //    this.window_help.timer = this.config.help_timer;
            //    const countdown = () => {
            //        this.window_help.timer--;
            //        if (this.window_help.timer > 0) {
            //            setTimeout(countdown, 1000);
            //        } else {
            //            this.window_help.activeElement = null;
            //        }
            //    };

            //    setTimeout(countdown, 1000);
            //}
        },
        closeHelp() {
            this.window_help.activeElement = null;
        }
    }
};
</script>
