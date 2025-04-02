<template>
    <section class="search-widget-ResultList">
        <div v-if="items" class="container">
            <section v-for="ship in items" class="ship__top">
                <div class="ship__item d-flex flex-column flex-lg-row justify-content-between mb-4 rounded">
                    <div class="ship__item-left">
                        <div class="ship__item-left_img" :style="`background-image: url(${ ship.pic })`"></div>

                        <template v-if="ship.youtube_link">
                            <a class="ship__item-left_youtube"  modal=".youtube-content">
                                <img src="/themes/azimut-tur-pro/assets/images/components/cruise/youtube.svg" alt="youtube"/>
                            </a>
                            <div class="youtube-content" style="display: none">
                                <iframe :src="ship.youtube_link" loading="lazy" frameborder="0" allowfullscreen=""></iframe>
                            </div>
                        </template>

                        <span
                            v-if="ship.motorship_status"
                            @click="record_with_status = ship"
                            style="cursor: pointer"
                            class="ship-type premium d-block d-md-none bg-gold-200 px-2 px-xxl-3  ms-2 py-2 py-xxl-2 c-primary-100 rounded-pill fw-bolder d-flex align-items-center justify-content-center">
                                 {{ ship.motorship_status }}
                        </span>
                    </div>
                    <div class="ship__item-right fs-s fs-xl-def">
                        <div class="ship__item-right_center d-flex justify-content-between px-2 px-sm-3 py-2 py-sm-3 flex-column">
                            <div class="ship__item-name d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <img src="/themes/azimut-tur-pro/assets/images/components/cruise/ship.svg" :alt="ship.name"/>
                                    <a :href="`/russia-river-cruises/motorship/${ ship.motorship_id }`" target="_blank" class="ms-2 fs-h3 fs-xl-h2 fw-bolder c-blue-300">{{ ship.name }}</a>
                                </div>
                                <span
                                    v-if="ship.motorship_status"
                                    @click="record_with_status = ship"
                                    style="cursor: pointer"
                                    class="ship-type premium d-none d-md-block bg-gold-200 px-2 px-xxl-3  ms-2 py-2 py-xxl-2 c-primary-100 rounded-pill fw-bolder d-flex align-items-center justify-content-center">
                                        {{  ship.motorship_status  }}
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-end">
                                <div class="ship__item-params">
                                    <p v-for="item in ship.techs">
                                        {{ item.name }}: {{ item.value }}
                                    </p>
                                </div>
                                <div
                                    class="result-item__discounts"
                                    style="cursor: pointer"
                                    v-if="hasDiscounts(ship)"
                                    @click="record_with_discounts = ship">
                                    <img src="/themes/prokruiz/assets/img/svg/discount.svg" alt="Скидки">
                                    Акции
                                    <i class="bi bi-caret-down-fill"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <DiscountsModal :record="record_with_discounts" @close="record_with_discounts = null" />
        <StatusModal :record="record_with_status" @close="record_with_status = null" />
    </section>
</template>

<script>
import DiscountsModal from "../../search-widget/components/DiscountsModal";
import StatusModal from "../../search-widget/components/StatusModal";
export default {
    name: "ShipsResults",
    components: { DiscountsModal, StatusModal },
    props: {
        items: null
    },
    data() {
        return {
            record_with_discounts: null,
            record_with_status: null
        }
    },
    methods: {
        hasDiscounts(record)
        {
            return (record.permanent_discounts && record.permanent_discounts.length)
                || (record.temporary_discounts && record.temporary_discounts.length)
        }
    }
}
</script>
