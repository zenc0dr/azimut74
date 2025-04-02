<template>
    <div>
        <div v-if="items.length" class="dw-results">
            <!--<div>Ширина: {{ width }}</div> -->

            <!-- Каталог -->
            <template v-if="items[0].type=='catalog'">
                <template v-for="item in items">
                    <div class="dw-result-box catalog">
                        <div class="dw-pic" :style="'background-image: url('+checkPath(item.snippet)+')'"></div>
                        <!-- <div class="dw-pic" :style="'background-image: url('+settings.domain+item.snippet+')'"></div> -->
                        <div class="dw-box-body">
                            <div class="dw-tour-name" :onclick="`window.open('${prepareUrl(item.url)}')`">{{ item.tour_name }}<span v-if="item.days_text">  , тур на {{ item.days_text }}</span></div>
                            <div class="dw-result-box_content-wrap">
                                <div class="dw-waybill">
                                    <template v-if="item.waybill && item.waybill.length">
                                        <template v-for="(point, key) in item.waybill">
                                            <span>{{ point }}</span>
                                            <span v-if="key < item.waybill.length - 1" class="separator">-</span>
                                        </template>
                                    </template>
                                </div>
                                <div class="dw-price-wrap">
                                    <div class="dw-price-btn" :onclick="`window.open('${prepareUrl(item.url)}')`">
                                        от {{ item.price }} руб
                                        <template v-if="item.source == 'a'"> / чел</template>
                                        <template v-if="item.source == 'd'"> /
                                        {{ ext_form.adults }} взр
                                            <template v-if="ext_form.childrens">
                                                , {{ ext_form.childrens }} реб
                                            </template>
                                        </template>
                                        <i class="fa fa-arrow-right"></i>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </template>

            <!-- Расписание -->
            <template v-if="items[0].type=='schedule'">
                <template v-for="item in items">
                    <div class="dw-result-box">
                        <div class="dw-pic" :style="'background-image: url('+checkPath(item.snippet)+')'"></div>
                        <div class="dw-box-body schedule">
                            <div class="dw-schedule-info">
                                <div class="dw-schedule-info-names">
                                    <div class="dw-schedule-info__tour-name" :onclick="`window.open('${prepareUrl(item.url)}')`">
                                        {{ item.tour_name }}
                                    </div>
                                    <div class="dw-schedule-info__tour-dates">
                                        <div v-html="item.date"></div>
                                        <div>{{ item.days }}</div>
                                    </div>

                                </div>
                                <div v-if="width > 737" class="dw-schedule-info__tour-price">
                                    <div class="dw-price-btn" :onclick="`window.open('${prepareUrl(item.url)}')`">
                                        от {{ item.price }} руб
                                        <template v-if="item.source == 'a'"> / чел</template>
                                        <template v-if="item.source == 'd'"> /
                                            {{ ext_form.adults }} взр
                                            <template v-if="ext_form.childrens">
                                                , {{ ext_form.childrens }} реб
                                            </template>
                                        </template>
                                        <i class="fa fa-arrow-right"></i>
                                        <div v-if="item.title" class="dw-offer-price__reduct-title" :class="{accent:item.accent}">
                                            {{ item.title }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="item.waybill && item.waybill.length" class="dw-schedule-info__tour-waybill">
                                {{ item.waybill | waybillString }}
                            </div>
                        </div>
                        <div v-if="width <= 737" class="dw-schedule-info__tour-price-mobile">
                            <div class="dw-price-btn" :onclick="`window.open('${prepareUrl(item.url)}')`">
                                от {{ item.price }} руб
                                <template v-if="item.source == 'a'"> / чел</template>
                                <template v-if="item.source == 'd'"> /
                                    {{ ext_form.adults }} взр
                                    <template v-if="ext_form.childrens">
                                        , {{ ext_form.childrens }} реб
                                    </template>
                                </template>
                                <i class="fa fa-arrow-right"></i>
                                <div v-if="item.title" class="dw-offer-price__reduct-title" :class="{accent:item.accent}">
                                    {{ item.title }}
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </template>


            <!-- Список предложений -->
            <template v-if="items[0].type=='offer'">

                <!-- Заголовки -->
                <template v-if="width > 700">
                    <div class="dw-result-box titles">
                        <div class="dw-box-body offer">
                            <div class="dw-offer-date">
                                Заезд
                            </div>
                            <div class="dw-offer-days">

                            </div>

                            <template v-if="source_code == 'd'">
                                <div class="dw-offer-tarrif dolphin-tour">
                                    Гостиница
                                </div>
                                <div class="dw-offer-room">
                                    Номер
                                </div>
                            </template>
                            <template v-if="source_code == 'a'">
                                <div class="dw-offer-tarrif azimut-tour">
                                    Гостиница, номер <br>
                                    Вариант размещения
                                </div>
                            </template>

                            <div class="dw-offer-pansion">
                                Питание
                            </div>

                            <div v-if="source_code == 'd'" class="dw-offer-price dolphin-tour">
                                Общая цена тура (на всех участников)
                            </div>
                            <div v-if="source_code == 'a'" class="dw-offer-price azimut-tour">
                                Общая цена тура (на всех участников)
                            </div>

                            <div class="dw-offer-submit">

                            </div>
                        </div>
                    </div>
                </template>

                <!-- Список -->
                <template v-if="width > 700">
                    <div v-for="item in items" class="dw-result-box">
                        <div class="dw-box-body offer">
                            <div class="dw-offer-date">
                                <div>
                                    <div>{{ item.date.d1 }}</div>-<div>{{ item.date.d2 }}</div>
                                </div>
                                <div>
                                    <div>{{ item.date.d1d }}</div><div>{{ item.date.d2d }}</div>
                                </div>
                            </div>
                            <div class="dw-offer-days">
                                {{ item.days }} Дн
                            </div>

                            <template v-if="source_code == 'd'" >
                                <div class="dw-offer-tarrif dolphin-tour">
                                    <template v-if="item.tarrif_name">
                                        <span >{{ item.tarrif_name }}</span><br>
                                    </template>
                                    <span>{{ item.hotel_name }}</span>
                                </div>
                                <div class="dw-offer-room">
                                    {{ item.room }}<br>{{ item.roomc }}
                                </div>
                            </template>

                            <template v-if="source_code == 'a'" >
                                <div class="dw-offer-tarrif azimut-tour">
                                    {{ item.hotel_name }}, {{ item.number_name }}
                                    <div class="dw-offer-placement">
                                        {{ item.placement }}
                                    </div>
                                </div>
                            </template>

                            <div class="dw-offer-pansion">
                                {{ item.pansion_name }}
                            </div>
                            <div v-if="source_code == 'd'" class="dw-offer-price dolphin-tour">
                                <div class="dw-offer-price__title">
                                    Общая цена тура на {{ ext_form.adults }} взр
                                    <template v-if="ext_form.childrens">
                                        , {{ ext_form.childrens }} реб
                                    </template>
                                </div>
                                {{ item.price }} руб
                            </div>
                            <div v-if="source_code == 'a'" class="dw-offer-price azimut-tour">
                                <div class="dw-offer-price__title">
                                    Общая цена тура на {{ item.consist }}
                                </div>

                                <div v-if="item.price_old" class="dw-offer-price__old">
                                    {{ item.price_old | priceFormat }}
                                </div>

                                <div class="dw-offer-price__sum">
                                    {{ item.price | priceFormat }}
                                </div>

                                <div v-if="item.title" class="dw-offer-price__reduct-title" :class="{accent:item.accent}">
                                    {{ item.title }}
                                </div>
                            </div>
                            <div class="dw-offer-submit">
                                <div class="dw-price-btn" @click="openOrder(item)">Бронировать</div>
                            </div>
                        </div>
                    </div>
                </template>
                <template v-else>
                    <div v-for="item in items" class="dw-result-box mobile">
                        <div class="dw-offer-mobile">
                            <div class="dwm-row">
                                <div class="dwm-col-title">
                                    Заезд:
                                </div>
                                <div class="dwm-col-data">
                                    <div class="dw-offer-date-mobile">
                                        <span class="dwm-date">{{ item.date.d1 }}</span>
                                        <span class="dwm-day">{{ item.date.d1d }}</span>
                                        -
                                        <span class="dwm-date">{{ item.date.d2 }}</span>
                                        <span class="dwm-day">{{ item.date.d2d }}</span>
                                        <span class="dwm-days">{{ item.days }} Дн</span>
                                    </div>
                                </div>
                            </div>
                            <div class="dwm-row">
                                <div class="dwm-col-title">
                                    Гостиница / Номер:
                                </div>
                                <div class="dwm-col-data">
                                    <template v-if="source_code == 'd'">
                                        <template v-if="item.tarrif_name">
                                            <div>{{ item.tarrif_name }}</div>
                                        </template>
                                        <div>{{ item.hotel_name }}</div>
                                        <div>{{ item.room }}, {{ item.roomc }}</div>
                                    </template>
                                    <template v-if="source_code == 'a'">
                                        {{ item.hotel_name }}, {{ item.number_name }}
                                    </template>
                                </div>
                            </div>
                            <div class="dwm-row">
                                <div class="dwm-col-title">
                                    Питание:
                                </div>
                                <div class="dwm-col-data">
                                    {{ item.pansion_name }}
                                </div>
                            </div>
                            <template v-if="source_code == 'd'">
                                <div class="dwm-row">
                                    <div class="dwm-col-title">
                                        Цена (общая, на всех туристов):
                                    </div>
                                    <div class="dwm-col-data price">
                                        {{ item.price }} руб
                                    </div>
                                </div>
                            </template>
                            <template v-if="source_code == 'a'">
                                <div class="dwm-row">
                                    <div class="dwm-col-title">
                                        Состав:
                                    </div>
                                    <div class="dwm-col-data">
                                        {{ item.consist }}
                                    </div>
                                </div>
                                <div class="dwm-row">
                                    <div class="dwm-col-title">
                                        Цена (общая, на всех туристов):
                                    </div>
                                    <div class="dwm-col-data price">
                                        <div v-if="item.price_old" class="dwm-price_old">{{ item.price_old | priceFormat }}</div>
                                        {{ item.price | priceFormat }}
                                    </div>
                                </div>
                                <div v-if="item.title" class="dwm-row-title" :class="{accent:item.accent}">
                                    {{ item.title }}
                                </div>
                            </template>
                            <div class="dwm-row-submit">
                                <div class="dw-price-btn" @click="openOrder(item)">Бронировать</div>
                            </div>
                        </div>
                    </div>
                </template>


            </template>

        </div>
        <div v-if="!items.length && success">
            Ничего не найдено. Попробуйте изменить параметры поиска
        </div>
    </div>
</template>
<script>
    export default {
        props: ['items', 'empty', 'success'],
        data() {
            return {
                page: 1
            }
        },
        computed: {
            settings() {
                return this.$store.getters.getSettings
            },
            widget_name() {
                return this.settings.widgetName
            },
            source_code() {
                return this.$store.getters.getExpSourceCode
            },
            width() {
                return this.$store.getters.getWidth
            },
            ext_form() {
                return this.$store.getters.getExtForm
            }
        },
        filters: {
            priceFormat(value) {
                value += "";
                return value.replace(/(\d{1,3})(?=((\d{3})*)$)/g, "  $1");
            },
            waybillString(array) {
                if(!array) return
                return array.join(' - ')
            }
        },
        methods: {
            openOrder(item)
            {
                this.$store.commit('setExtFormData')
                this.$store.commit('buildExtOrder', item)
            },
            prepareUrl(url)
            {
                let wName = this.widget_name

                if(wName == 'ext') wName = 'exp'
                if(wName == 'atm') wName = 'atp'

                return '/ex-tours/'+wName+'/'+url
            },
            checkPath(path) {
                if (!path) return
                if(path.indexOf('/') !== 0) return path
                return this.settings.domain+path
            }
        }
    }
</script>
