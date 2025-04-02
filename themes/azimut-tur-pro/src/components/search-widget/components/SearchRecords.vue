<template>
    <div id="Result" :class="{preloader}">
        <template v-for="record in items">
            <section v-if="!record.injection"
                     class="result-item d-flex flex-column flex-md-row bg-primary-100 mb-4 rounded">
                <div class="result-item__left col-12 col-md-4 position-relative">
                    <img class="result-item__img h-100 rounded-top rounded-md-left" :src="record.image" alt="item">
                    <template v-if="record.motorship_status">
                        <div @click="record_with_status = record"
                             class="result-item__tag d-block d-md-none bg-gold-200 fs-s fs-sm-def px-2 px-xxl-3  ms-0 py-2 py-xxl-2 c-primary-100 rounded-pill fw-bolder d-flex align-items-center justify-content-center">
                            <span>{{ record.motorship_status }}</span>
                        </div>
                    </template>
                    <template v-if="record.youtube">
                        <a class="result-item__left-youtube" :href="record.youtube" modal=".youtube-content">
                            <img src="/themes/azimut-tur-pro/assets/images/components/cruise/youtube.svg" alt="youtube">
                        </a>
                        <div class="youtube-content" style="display: none">
                            <iframe :src="record.youtube" loading="lazy" frameborder="0" allowfullscreen=""></iframe>
                        </div>
                    </template>
                </div>

                <div class="col-12 col-md-8 d-flex flex-column">
                    <div class="result-item__center fs-s fs-sm-def fs-md-s fs-xxl-def col p-3 p-md-2 p-xl-3">
                        <div class="result-item__line-name d-flex align-items-start justify-content-between">
                            <div
                                class="result-item__wrapper-name d-flex px-3 py-3 px-md-0 py-md-0 align-items-center justify-content-start w-100 w-md-auto">
                                <img class="result-item__icon-ship me-0 me-md-2"
                                     src="/themes/azimut-tur-pro/assets/images/components/result-item/ship.svg"
                                     alt="icons">
                                <a
                                    target="_blank"
                                    :href="`/russia-river-cruises/motorship/${record.motorship_id}`"
                                    class="fs-h3 fs-sm-h2 fs-md-def fs-xxl-h3 ms-2 ms-md-0 fw-bolder">
                                    {{ record.motorship_name }}
                                </a>
                            </div>
                            <template v-if="record.motorship_status">
                                <div @click="record_with_status = record"
                                     class="result-item__tag d-none d-md-block bg-gold-200 px-2 px-xxl-3  ms-2 py-2 py-xxl-2 c-primary-100 rounded-pill fw-bolder d-flex align-items-center justify-content-center">
                                    <span>{{ record.motorship_status }}</span>
                                </div>
                            </template>
                        </div>
                        <div
                            class="result-item__date d-flex flex-column flex-sm-row align-items-start my-1 my-sm-3 mb-3 mb-sm-2">
                            <div class="result-item__date-left d-flex flex-column align-items-center">
                                <div class="d-flex align-items-end">
                                    <img class="me-1 me-xl-2"
                                         src="/themes/azimut-tur-pro/assets/images/components/result-item/calendar.svg">
                                    <b>{{ record.date.d1 }}</b>, {{ record.date.d1d }} ({{ record.date.t1 }}) <span
                                    class="px-1">—</span> <b>{{ record.date.d2 }}</b>, {{ record.date.d2d }}
                                    ({{ record.date.t2 }})
                                </div>
                                <div class="fs-ss fs-xxl-s c-red-200">Время московское</div>

                            </div>
                            <div class="result-item__date-right d-flex align-items-center ms-0 ms-sm-3 ms-xl-5">
                                <img class="me-1 me-xl-2"
                                     src="/themes/azimut-tur-pro/assets/images/components/result-item/clock.svg">
                                {{ record.days }}
                                 <template v-if="widget_history">
                                    <div class="widget-history__favorite"
                                        :widget-id="record.id"
                                        :title-element="record.motorship_name"
                                        :url="`/russia-river-cruises/cruise/${ record.id }`"
                                        type="cruise"
                                        :days="getDays(record)"
                                        title="Добавить в избранное"
                                        :other="getWaybill(record.waybill)"
                                        onclick="APP.clickFavorite(this)"
                                        >
                                        <img class="active" src="/plugins/zen/history/assets/images/icons/heart-active-1.svg">
                                        <img class="no-active" src="/plugins/zen/history/assets/images/icons/heart.svg">
                                    </div>
                                </template>
                            </div>
                        </div>
                        <div class="result-item__routes" v-html="record.waybill"></div>
                    </div>
                    <div
                        class="result-item__right d-flex d-none d-md-block p-3 p-md-2 p-xl-3 align-items-center justify-content-between">
                        <div class="result-item__right-top">
                            <div
                                class="result-item__discounts"
                                v-if="hasDiscounts(record)"
                                @click="record_with_discounts = record">
                                <img src="/themes/prokruiz/assets/img/svg/discount.svg" alt="Скидки">
                                Акции
                                <i class="bi bi-caret-down-fill"></i>
                            </div>
                        </div>
                        <div
                            class="result-item__right-bottom d-flex flex-column flex-md-row align-items-center justify-content-end">
                            <div class="text-center me-0 me-md-3 mb-3 mb-md-0 fs-def fs-md-s fs-xxl-def">
                                <div>от <span class="fs-h2 fs-sm-h1 fs-md-h2 fs-xxl-h1 fw-bolder">{{
                                        record.price_start
                                    }}</span> р./чел
                                </div>
                                <div class="fs-ss">без учёта скидок</div>
                            </div>
                            <div class="text-center">
                                <a class=" result-item__booking d-flex flex-column bg-red-200 c-primary-100 text-decoration-none rounded px-5 px-md-2 py-3 py-md-2"
                                   :href="`/russia-river-cruises/cruise/${ record.id }`" target="_blank">
                                    <span class="fs-s fs-sm-def fs-md-ss fs-xl-s">наличие мест, цены</span>
                                    <span class="text-uppercase fw-bolder fs-h3 fs-sm-h2 fs-md-def fs-xxl-h2">Бронирование</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="result-item__right d-flex flex-column d-block d-md-none">
                        <div class="d-flex flex-row justify-content-center">
                            <div
                                class="result-item__discounts mb-3 pe-5"
                                v-if="hasDiscounts(record)"
                                @click="record_with_discounts = record">
                                <img src="/themes/prokruiz/assets/img/svg/discount.svg" alt="Скидки">
                                Акции
                                <i class="bi bi-caret-down-fill"></i>
                            </div>
                            <div class="text-center me-0 me-md-3 mb-3 mb-md-0 fs-def fs-md-s fs-xxl-def">
                                <div>от <span class="fs-h2 fs-sm-h1 fs-md-h2 fs-xxl-h1 fw-bolder">{{
                                        record.price_start
                                    }}</span> р./чел
                                </div>
                                <div class="fs-ss">без учёта скидок</div>
                            </div>
                        </div>
                        <div class="text-center">
                            <a class=" result-item__booking d-flex flex-column bg-red-200 c-primary-100 text-decoration-none rounded px-5 px-md-2 py-3 py-md-2"
                               :href="`/russia-river-cruises/cruise/${ record.id }`" target="_blank">
                                <span class="fs-s fs-sm-def fs-md-ss fs-xl-s">наличие мест, цены</span>
                                <span
                                    class="text-uppercase fw-bolder fs-h3 fs-sm-h2 fs-md-def fs-xxl-h2">Бронирование</span>
                            </a>
                        </div>
                    </div>
                </div>
            </section>
            <template v-else>
                <LiveComponent
                    v-if="isJsonString(record.injection)"
                    :json-string="record.injection"
                />
                <SelfInjection v-else :html="record.injection"/>
            </template>
        </template>

        <DiscountsModal :record="record_with_discounts" @close="record_with_discounts = null"/>
        <StatusModal :record="record_with_status" @close="record_with_status = null"/>

    </div>
</template>

<script>
import DiscountsModal from "./DiscountsModal";
import StatusModal from "./StatusModal";
import SelfInjection from "../../vue-components/SelfInjection";
import LiveComponent from "./LiveComponent";

export default {
    name: "SearchRecords",
    components: {
        DiscountsModal, StatusModal, SelfInjection, LiveComponent
    },
    data() {
        return {
            record_with_discounts: null,
            record_with_status: null
        }
    },
    props: {
        preloader: {
            type: Boolean,
            default: false,
        },
        widget_history: {
            type: Boolean,
            default: true,
        },
        items: {
            type: Array,
            default: null
        }
    },
    methods: {
        getDays(item) {
            return `${ item.date.d1 }, ${ item.date.d1d } (${ item.date.t1 }) — ${ item.date.d2 }, ${ item.date.d2d } (${ item.date.t2 })`
        },
        hasDiscounts(record) {
            return (record.permanent_discounts && record.permanent_discounts.length)
                || (record.temporary_discounts && record.temporary_discounts.length)
        },
        isJsonString(str) {
            //console.log('isJsonString', str)
            try {
                JSON.parse(str);
            } catch (e) {
                return false;
            }
            return true;
        },
        getWaybill(waybill) {
            let other =  {
                'waybill': waybill
            }
            return JSON.stringify(other)
        }
    }
}
</script>
