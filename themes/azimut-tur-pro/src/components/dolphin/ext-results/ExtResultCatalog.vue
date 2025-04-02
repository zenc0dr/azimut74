<template>
    <div class="dw-result-box catalog">
        <div class="dw-pic" :style="`background-image:url(${ item.snippet })`"></div>
        <div class="dw-box-body">
            <div class="dw-tour-name" :onclick="`window.open('${ designUrl(item) }')`">
                {{ item.tour_name }}
                <span v-if="item.date_desc">  , тур на {{ item.days_desc }}</span>
                <span v-else>тур на {{ item.days }} дн.</span>
            </div>
            <div class="dw-result-box_content-wrap">
                <div v-if="item.qq" class="qq-btn-place" :data-value="JSON.stringify(item.qq)"></div>
                <div class="dw-waybill">
                    <template v-if="item.waybill && item.waybill.length">
                        <template v-for="(point, key) in item.waybill">
                            <span>{{ point }}</span>
                            <span v-if="key < item.waybill.length - 1" class="separator">-</span>
                        </template>
                    </template>
                </div>
                <div class="dw-price-wrap">
                    <div class="dw-price-btn" :onclick="`window.open('${ designUrl(item) }')`">
                        от {{ item.price }} руб / чел
                        <i class="fa fa-arrow-right"></i>
                    </div>
                </div>
            </div>
            <div v-if="labels || widget_history" class="dw-tour-bottom__wrapper">
                <div v-if="labels" class="dw-tour-labels">
                    <div v-for="label in labels" @click="$emit('show-label', label)" class="dw-tour-label">
                        {{ label.name }}
                    </div>
                </div>
                <div v-if="widget_history" class="widget-history__favorite"
                        :widget-id="item.id"
                        type="group-tours"
                        :title-element="item.tour_name"
                        :url="designUrl(item)"
                        :days="getDays"
                        title="Добавить в избранное"
                        onclick="APP.clickFavorite(this)"
                        >
                        <img class="active" src="/plugins/zen/history/assets/images/icons/heart-active-1.svg">
                        <img class="no-active" src="/plugins/zen/history/assets/images/icons/heart.svg">
                </div>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    name: "ExtResultCatalog",
    props: {
        item: null,
        labels: {
            type: Array,
            default: null
        },
        designUrl: {
            type: Function,
            default: null
        },
        widget_history: {
            type: Boolean,
            default: true
        }
    },
    computed: {
        getDays() {

            return this.item.date_desc ? `на ${this.item.date_desc} дней` : `на ${this.item.days } дней`

        }
    }
}
</script>
