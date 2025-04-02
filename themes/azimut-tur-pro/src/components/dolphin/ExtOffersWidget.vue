<template>
    <div >
        <section v-if="data_loaded" class="dolphin-widget">
            <div class="input-block">
                <div class="input-block__label">Возможные даты</div>
                <div class="input-block__wrap">
                    <ZenSelect
                        :value="form.date"
                        :options="allowed_dates"
                        @change="form.date = $event"
                    />
                </div>
            </div>
            <div class="input-block">
                <div class="input-block__label">Состав</div>
                <div class="input-block__wrap">
                    <PeoplesSelector
                        :adults-max="max_adults"
                        :adults-min="1"
                        :adults="form.adults"
                        @adults-change="form.adults = $event"
                        :childrens-max="max_childrens"
                        :childrens="form.childrens"
                        @childrens-change="form.childrens = $event"
                    />
                </div>
            </div>
        </section>
        <div class="dw-results mt-4">
            <!-- Заголовки -->
            <!-- :todo deprecated by https://8ber.kaiten.ru/space/45609/card/4842489
            <template v-if="width > 700">
                <div class="dw-result-box titles">
                    <div class="dw-box-body offer">
                        <div class="dw-offer-date">
                            Заезд
                        </div>
                        <div class="dw-offer-days"></div>
                        <template>
                            <div class="dw-offer-tarrif azimut-tour">
                                Гостиница, номер <br>
                                Вариант размещения
                            </div>
                        </template>
                        <div class="dw-offer-pansion">
                            Питание
                        </div>
                        <div class="dw-offer-price azimut-tour">
                            Общая цена тура (на всех участников)
                        </div>
                        <div class="dw-offer-submit"></div>
                    </div>
                </div>
            </template>
            -->
            <!-- Список -->
            <template v-if="width > 700">
                <div v-for="item in result_items" class="offers">
                    <div class="offers__body">
                        <div class="offers__date">
                            <div>
                                <div>{{ item.date.d1 }}</div>-<div>{{ item.date.d2 }}</div></div>
                            <div>
                                <div>{{ item.date.d1d }}</div><div>{{ item.date.d2d }}</div>
                            </div>
                        </div>
                        <div class="offers__days">
                            {{ item.days }} Дн
                        </div>
                        <div class="offers__info">
                            <div class="offers__info__main">
                                <div class="offers__hotel">
                                    <div><b>Отель:</b> {{ item.hotel_name }}</div>
                                    <div><b>Номер:</b> {{ item.number_name }}</div>
                                    <div><b>Размещение:</b> {{ item.placement }}</div>
                                    <div><b>Питание:</b> {{ item.pansion_name }}</div>
                                </div>
                                <div class="offers__prices">
                                    <div class="offers__peoples">
                                        <div>Общая цена тура на</div>
                                        <div>{{ item.placement }}</div>
                                    </div>
                                    <div class="offers__price">
                                        {{ item.price.sum|priceFormat }} руб.
                                    </div>
                                    <div class="offers__switch"
                                         @click="dropdown(item)">
                                        <template v-if="item.additional">
                                            Скрыть детализацию
                                        </template>
                                        <template v-else>
                                            Детализация цены
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <transition name="slide">
                                <div v-if="item.additional" class="offers__info__additional">
                                    <div v-for="(price_item, p_i) in item.price.chain">
                                        <div>
                                            <div class="add-code">
                                                {{ price_item.code }}
                                            </div>
                                            <div class="add-price">
                                                {{ price_item.price|priceFormat }} руб.
                                            </div>
                                        </div>
                                        <div class="add-plus">
                                            <template v-if="p_i < item.price.chain.length - 1">
                                                +
                                            </template>
                                            <template v-else>
                                                =
                                            </template>
                                        </div>
                                    </div>
                                    <div>
                                        {{ item.price.sum|priceFormat }} руб.
                                    </div>
                                </div>
                            </transition>
                        </div>
                        <div class="offers__booking">
                            <div class="offers__btn" @click="openOrder(item)">
                                Бронировать
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <template v-else>
                <div v-for="item in result_items" class="offers">
                    <div class="offers__body mobile">
                        <div>
                            <div>Заезд:</div>
                            <div>
                                <span class="mobile__date">
                                    <span>{{ item.date.d1 }} ({{ item.date.d1d }})</span> - <span>{{ item.date.d2 }} ({{ item.date.d2d }})</span>
                                </span>
                                <span class="mobile__days">{{ item.days }} Дн</span>
                            </div>
                        </div>
                        <div>
                            <div>Гостиница / Номер: </div>
                            <div>
                                {{ item.hotel_name }} / {{ item.number_name }}
                            </div>
                        </div>
                        <div>
                            <div>Питание:</div>
                            <div>
                                {{ item.pansion_name }}
                            </div>
                        </div>
                        <div class="price-line">
                            <div class="price-line__header">
                                Состав / Цена
                            </div>
                            <div class="price-line__body">
                                <div v-for="(price_item, p_i) in item.price.chain">
                                    <div>
                                        <div class="add-code">
                                            {{ price_item.code }}
                                        </div>
                                        <div class="add-price">
                                            {{ price_item.price|priceFormat }} руб.
                                        </div>
                                    </div>
                                    <div class="add-plus">
                                        <template v-if="p_i < item.price.chain.length - 1">
                                            +
                                        </template>
                                        <template v-else>
                                            =
                                        </template>
                                    </div>
                                </div>
                                <div class="offers__price">
                                    {{ item.price.sum|priceFormat }} руб.
                                </div>
                            </div>
                        </div>
                        <div class="offers__btn" @click="openOrder(item)">
                            Бронировать
                        </div>
                    </div>
                </div>
            </template>
        </div>
        <BookingApp
            :order="order"
            scope="ext"
            @close="order = null"
        />
    </div>
</template>

<script>
import api from '../vue-components/api';
import DatePicker from "../vue-components/DatePicker";
import PeoplesSelector from "../vue-components/PeoplesSelector";
import BookingApp from "./BookingApp";
import ZenSelect from "../vue-components/ZenSelect";

export default {
    name: "ExtOffersWidget",
    components: { DatePicker, PeoplesSelector, BookingApp, ZenSelect },
    data() {
        return {
            process: false,
            data_loaded: false,
            query_count: 0, // Счётчик запросов
            form: {
                geo_objects: [],
                date: null,
                days: [],
                adults: 1,
                childrens: [],
                list_type: 'offers'
            },

            tour_id: null,
            tour_name: null,

            result_items: null,
            allowed_dates: null,
            width: null, // Ширина

            /* Ограничения людей */
            max_people: 4, /* Максимально людей всего */
            max_adults: 4, /* Максимально взрослых */
            max_childrens: 2, /* Максимально детей */
            allowed_adults: 4,
            allowed_childrens: 2,

            order: null,
        }
    },
    watch: {
        form: {
            handler() {
                //console.log('form changed', this.allowed_dates)
                this.search()
            },
            deep: true
        }
    },
    created() {
        window.ExtOffersWidget = this
    },
    mounted()
    {
        this.bindWidthDetect()
        this.loadSearchData(() => {
            //this.search()
            this.data_loaded = true
        })
    },
    filters: {
        priceFormat(value) {
            value += "";
            return value.replace(/(\d{1,3})(?=((\d{3})*)$)/g, "  $1");
        }
    },
    methods: {
        bindWidthDetect()
        {
            $(document).ready(() => {
                this.setWidth()
            })

            $(window).resize(() => {
                this.setWidth()
            })
        },

        // Получить ширину контейнера
        setWidth()
        {
            this.width = $('.dw-results').width()
        },

        dropdown(item) {
            console.log('dropdown')
            item.additional = item.additional === undefined ? item.additional = true : item.additional = !item.additional
            this.$forceUpdate()
        },

        // Типовой запрос к api
        apiQuery(opts)
        {
            this.process = true
            api.sync({
                url: '/zen/dolphin/api/' + opts.method,
                data: opts.data,
                then: response => {
                    this.process = false
                    if (opts.then) {
                        opts.then(response)
                    }
                },
                error: error => {
                    console.log(error)
                    this.process = false
                }
            })
        },

        // Пересчёт и лимитирование состава туристов
        recountPeoples()
        {
            let peoples_count = this.form.adults + this.form.childrens

            if (peoples_count < this.max_people) {
                this.allowed_adults = this.max_adults
                this.allowed_childrens = this.max_childrens
            } else {
                this.allowed_adults = this.form.adults
                this.allowed_childrens = this.form.childrens
            }
        },

        // Загрузка начальных данных
        loadSearchData(fn)
        {
            let search_data = $('meta[name="offers-widget-data"]').attr('content')
            search_data = JSON.parse(search_data) // {"ad":2,"ch":[],"ds":"all","go":[],"id":148,"dt":"08.04.2022"}

            let days = this.date = search_data.ds
            if (days === 'all') {
                days = [1,2,3,4,5,6,7,8,9,10,11,12,13,14]
            }

            this.form.geo_objects = search_data.go
            this.form.date = search_data.dt
            this.form.days = days
            this.form.adults = search_data.ad
            this.form.childrens = search_data.ch
            this.tour_id = search_data.id
            this.tour_name = search_data.tour_name
            fn()
        },

        // Поиск
        search()
        {
            this.apiQuery({
                method: 'ext:search',
                data: {
                    geo_objects: this.form.geo_objects,
                    date: this.form.date,
                    tour_id: this.tour_id,
                    days: this.form.days,
                    adults: this.form.adults,
                    childrens: this.form.childrens,
                    list_type: 'offers'
                },
                then: response => {
                    this.allowed_dates = response.allowed_dates
                    this.result_items = response.result_items
                }
            })
        },

        openOrder(item)
        {
            this.order = {
                tour_id: this.tour_id,
                tour_name: this.tour_name,
                tarrif_name: item.tarrif_name,
                date_of: item.date.d1,
                date_of_dow: item.date.d1d,
                date_to: item.date.d2,
                date_to_dow: item.date.d2d,
                days: item.days,
                adults: this.form.adults,
                childrens: this.form.childrens,
                hotel_name: item.hotel_name,
                pansion: item.pansion_name,
                room: item.room,
                roomc: item.roomc,
                price: item.price
            }
        },
        ym(target)
        {
            if (typeof window['ym'] !== 'function') {
                return null;
            }
            ym(13605125,'reachGoal', target)
        }
    }
}
</script>
<style lang="scss">
.slide-enter-active {
    transition: 0.3s;
}

.slide-leave-active {
    transition: 0.3s;
}

.slide-enter-to, .slide-leave {
    max-height: 100px;
    overflow: hidden;
}

.slide-enter, .slide-leave-to {
    overflow: hidden;
    max-height: 0;
}
</style>
