<template>
    <div>
        <div v-if="items.length" class="atm-results">
            <div v-if="!resultKey" class="atm-results-info">
                Найдено вариантов: {{ items.length }}
            </div>
            <template v-if="(!resultKey || resultKey === item.result_key) && !item.injection" v-for="(item, i) in items">
                <div :style="`animation-duration:${ 200 + (i*50) }ms`"
                     class="atm-result-box" :op="item.op">
                    <div class="atm-result-preview" :style="`background-image:url(${ item.image })`"></div>
                    <div class="atm-result-content">
                        <div class="atm-result__wrapper-tour-name">
                            <div class="atm-result__tour-name" @click="openRecord(item)">{{ item.tour_name }}</div>
                            <div class="widget-history__favorite"
                                @click="openRecord(item, true, $event)"
                                :widget-id="item.tour_id"
                                :title-element="item.tour_name"
                                :url="temp_url"
                                :days="getDays(item)"
                                title="Добавить в избранное"
                                type="autobus-tours"
                                >
                                <img class="active" src="/plugins/zen/history/assets/images/icons/heart-active-1.svg">
                                <img class="no-active" src="/plugins/zen/history/assets/images/icons/heart.svg">
                            </div>
                        </div>
                        <div class="atm-result_sub-content">
                            <div class="atm-result_sub-content-info">
                                <div class="atm-result__info">
                                    <span v-if="item.geo_object" class="atm-result__info-geo_object">{{ item.geo_object }}</span>
                                    <span v-if="item.geo_object"> - </span>
                                    <span @click.self="openGPS(item)" v-if="item.gps" class="atm-result__info-gps">Открыть на карте</span>
                                    <span v-if="item.gps"> - </span>
                                    <span v-if="item.to_sea" class="atm-result__info-to_sea">До моря {{ item.to_sea }}м</span>
                                </div>
                                <div class="atm-result__apartments">
                                    {{ item.number_name }} / {{ item.consist }}
                                </div>
                                <div class="atm-result__pansion">
                                    {{ item.pansion_name }}
                                </div>
                                <div class="atm-result__timeline">
                                    С {{ item.date.d1 }} ({{ item.date.d1d }}) по {{ item.date.d2 }} ({{ item.date.d2d }}), {{ item.days }} дн
                                </div>
                            </div>
                            <div class="atm-result_sub-content-price">
                                <div class="atm-result__consist">
                                    Общая цена тура на:<br>
                                    {{ simple_consist }}
                                </div>
                                <div v-if="atp" class="atm-result__price-btn" @click="makeOrder(item)">
                                    {{ item.sum }} руб <br>
                                    Бронировать
                                </div>
                                <div v-else :class="{opening:opening_tour == item.result_key}" class="atm-result__price-btn" @click="openRecord(item)">
                                    {{ item.sum }} руб
                                    <i class="fa fa-arrow-right"></i>
                                </div>

                                <div class="atm-result__show-priceinfo">
                                    Детализация цены
                                </div>
                            </div>
                        </div>
                        <div class="atm-result__priceinfo">
                            <div class="atm-result-priceline">
                                <template v-for="(price, p_i) in item.price.chain">
                                    <div class="atm-result-priceline-block">
                                        <div class="atm-pl-code">
                                            {{ price.code }}
                                        </div>
                                        <div class="atm-pl-price">
                                            {{ price.price }} руб.
                                        </div>
                                    </div>
                                    <div class="atm-result-priceline-plus">
                                        <template v-if="p_i < item.price.chain.length - 1">
                                            +
                                        </template>
                                        <template v-else>
                                            =
                                        </template>
                                    </div>
                                </template>
                                <div class="atm-result-priceline-sum">
                                    {{ item.price.sum }} руб.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <template v-else>
                <LiveComponent
                    v-if="isJsonString(item.injection)"
                    :json-string="item.injection"
                />
                <SelfInjection v-if="!isJsonString(item.injection)" :html="item.injection"/>
            </template>
        </div>
        <div class="atm-not-found" v-if="!items.length && runs_count > 1 && !process">
            Ничего не найдено. Попробуйте изменить параметры поиска
        </div>
        <zen-modal max-width="1000px" z-index="100500" title="Карта" :show="(gps) ? true : false" @hide="gps = null">
            <div class="iframe-preloader" :style="`background-image: url(${settings.domain}/plugins/zen/dolphin/assets/images/preloaders/cubesline.gif)`">
                <iframe id="hmap" class="atm-iframe-map" :src="`${settings.domain}/zen/dolphin/api/atm:openGPS?gps=${gps}&html=true`" frameborder="0"></iframe>
            </div>
        </zen-modal>
    </div>

</template>
<script>
    import $ from 'jquery'
    import ZenModal from "./ZenModal";
    import SelfInjection from "./SelfInjection";
    import LiveComponent from "./LiveComponent";

    export default {
        name: 'AtmResults',
        components: {
            ZenModal, SelfInjection, LiveComponent
        },
        props: {
            items: {
                type: Array,
                default: null
            },
            resultKey: {
                type: String,
                default: null
            },
            atp: {
                type: Boolean,
                default: false
            }
        },
        data() {
            return {
                opening_tour: false,
                runs_count: 0,
                gps: null,
                temp_url: ''
            }
        },
        watch: {
            items() {
                this.runs_count++
            }
        },
        mounted() {
            $(document).on('click', '.atm-result__show-priceinfo', function () {
                $(this).closest('.atm-result-content').find('.atm-result__priceinfo').slideToggle(200)
            })
        },
        computed: {

            settings() { return this.$store.getters.getSettings },
            adults() { return this.$store.getters.getAtmAdults },
            children_ages() { return this.$store.getters.getAtmChildrenAges },
            simple_consist() {
                let output = this.adults + ' взр'
                if(this.children_ages && this.children_ages.length) {
                    output += ' +' + this.children_ages.length + ' реб (' + this.children_ages.join(',') + ')'
                }
                return output
            },
            process() { return this.$store.getters.getProcess },
        },
        methods: {
            getDays(item) {
                return `С ${item.date.d1 } (${ item.date.d1d }) по ${ item.date.d2 } (${ item.date.d2d }), ${ item.days } дн`
            },
            openGPS(item){
                this.gps = item.gps
            },
            openRecord(item, onlyLink, e)
            {
                if(this.opening_tour) return
                this.temp_url = ''
                this.opening_tour = item.result_key

                let link_set = {
                    tour_id: item.tour_id,
                    geo_objects: this.$store.getters.getAtmGeoObjects,
                    adults: this.adults,
                    childrens: this.children_ages,
                    dates: this.$store.getters.getAtmDates,
                    comforts: this.$store.getters.getAtmSelectedComforts,
                    services: this.$store.getters.getAtmSelectedServices,
                    to_sea: this.$store.getters.getAtmSelectedToSea,
                    pansions: this.$store.getters.getAtmSelectedPansions,
                    hotel_id: item.hotel_id,
                    result_key: item.result_key,
                    tour_date: item.date,
                }

                this.$store.dispatch('apiQuery', {
                    url: 'atm:makeLink',
                    data: {link_set},
                    then: response => {
                        let href = location.pathname
                        href = href.replace(/\/h\d+/, '') // Санитизация от меток отеля ex: /h23425
                        href = href.replace('/atm/', '/atp/')
                        href = href + '/' + response.link
                        if (onlyLink) {
                            this.temp_url = href
                            setTimeout(() => {
                                APP.clickFavorite(e.target.parentElement)
                            }, 300)
                            this.opening_tour = false
                            return true
                        }
                        window.open(href);
                        this.opening_tour = false
                    }
                })
            },

            makeOrder(item)
            {
                this.$store.commit('buildAtmOrder', item)
            },
            isJsonString(str) {
                //console.log('isJsonString', str)
                try {
                    JSON.parse(str);
                } catch (e) {
                    return false;
                }
                return true;
            }
        }
    }
</script>
