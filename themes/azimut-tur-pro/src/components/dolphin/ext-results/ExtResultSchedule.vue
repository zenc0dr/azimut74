<template>
    <div class="dw-result-box">
        <div class="dw-pic" :style="`background-image:url(${ item.snippet })`"></div>
        <div class="dw-box-body schedule">
            <div class="dw-schedule-info">
                <div class="dw-schedule-info-names">
                    <div class="dw-schedule-info__tour-name" :onclick="`window.open('${ designUrl(item) }')`">
                        {{ item.tour_name }}
                    </div>
                    <div class="dw-schedule-info__tour-dates">
                        <div>{{ item.date_desc }}</div>
                        <div>{{ item.days_desc }}</div>
                    </div>
                </div>
                <div v-if="width > 737" class="dw-schedule-info__tour-price">
                    <div class="dw-price-btn" :onclick="`window.open('${ designUrl(item) }')`">
                        от {{ item.price }} руб / чел
                        <i class="fa fa-arrow-right"></i>
                    </div>
                </div>
            </div>
            <div v-if="item.waybill && item.waybill.length" class="dw-schedule-info__tour-waybill">
                {{ item.waybill | waybillString }}
            </div>
            <div v-if="labels || widget_history" class="dw-tour-bottom__wrapper">
                <div v-if="labels" class="dw-tour-labels">
                    <div v-for="label in labels" @click="$emit('show-label', label)" class="dw-tour-label">
                        {{ label.name }}
                    </div>
                </div>
                <div v-if="widget_history" class="widget-history__favorite"
                    :widget-id="item.id"
                    type="tours"
                    title="Добавить в избранное"
                    :title-element="item.tour_name"
                    :url="designUrl(item)"
                    :days="item.date_desc"
                    onclick="APP.clickFavorite(this)"
                    >
                    <img class="active" src="/plugins/zen/history/assets/images/icons/heart-active-1.svg">
                    <img class="no-active" src="/plugins/zen/history/assets/images/icons/heart.svg">
                </div>
            </div>

            <div v-if="item.qq" class="qq-btn-place" :data-value="qqRender(item)"></div>
        </div>
        <div v-if="width <= 737" class="dw-schedule-info__tour-price-mobile">
            <div class="dw-price-btn" :onclick="`window.open('${ designUrl(item) }')`">
                от {{ item.price }} руб / чел
                <i class="fa fa-arrow-right"></i>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "ExtResultSchedule",
    props: {
        item: null,
        width: null,
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
    filters: {
        priceFormat(value) {
            value += "";
            return value.replace(/(\d{1,3})(?=((\d{3})*)$)/g, "  $1");
        },
        waybillString(array) {
            if (!array) {
                return
            }
            return array.join(' - ')
        }
    },
    methods: {
        qqRender(item) {
            item.qq.href = location.origin + this.designUrl(item)
            return JSON.stringify(item.qq)
        },
    }
}
</script>
