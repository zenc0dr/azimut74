<template>
    <div v-if="items && items.length && width">
        <div class="st-table" v-if="width >= minWidth">
            <div class="st-titles">
                <template v-if="dates_enabled">
                    <div class="st-arrival">Выезд</div>
                    <div class="st-departure">Прибытие</div>
                </template>
                <div class="st-days">Дней</div>
                <div class="st-tour">Тур</div>
                <div class="st-price">Руб/чел</div>
            </div>
            <div v-if="ifRowShow(index, item)" v-for="(item, index) in items" class="st-item">
                <template v-if="dates_enabled">
                    <div class="st-departure">{{ item.departure }}</div>
                    <div class="st-arrival">{{ item.arrival }}</div>
                </template>
                <div class="st-days">{{ item.days }}</div>
                <div class="st-tour">
                    <div class="st-tour-name">
                        <a :href="designUrl(item)" target="_blank">
                            {{ item.tour_name }}
                        </a>
                    </div>
                    <div class="st-waybill">{{ item.waybill.join(' - ') }}</div>
                </div>
                <div class="st-price">{{ item.price }}</div>
            </div>
        </div>
        <div v-else class="st-cards">
            <div v-if="ifRowShow(index, item)" v-for="(item, index) in items" class="st-card">
                <div class="st-card__header">
                    <div class="st-card__titles">
                        <template v-if="dates_enabled">
                            <div class="st-departure">Выезд</div>
                            <div class="st-days">Дней</div>
                        </template>
                        <div class="st-arrival">Прибытие</div>
                    </div>
                    <div class="st-card__time">
                        <template v-if="dates_enabled">
                            <div class="st-departure">{{ item.departure }}</div>
                            <div class="st-days">{{ item.days }}</div>
                        </template>
                        <div class="st-arrival">{{ item.arrival }}</div>
                    </div>
                </div>
                <div class="st-tour">
                    <div class="st-tour-name">
                        <a :href="designUrl(item)">
                            {{ item.tour_name }}
                        </a>
                    </div>
                    <div class="st-waybill">{{ item.waybill.join(' - ') }}</div>
                </div>
                <div class="st-price">
                    Цена: <span>{{ item.price }}</span> Руб/чел
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "ScTable",
    props: {
        items: [],
        width: null,
        minWidth: null,
        dates_enabled: {
            type: Boolean,
            default: true
        },
        resolving: {
            Type: Function,
            default: null
        },
        designUrl: {
            type: Function,
            default: function (item) {
                return item.url
            }
        }
    },
    methods: {
        ifRowShow(index, item)
        {
            if (this.resolving === null) {
                return true
            }
            return this.resolving(index, item)
        }
    }
}
</script>
