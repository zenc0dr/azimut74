<template>
    <section class="reviews-widget mt-4" v-if="ready">
        <div class="reviews-widget__panel">
            <select v-model="selectedShipId" class="reviews-widget__select">
                <option value="">Все теплоходы</option>
                <option v-for="(name, id) in ships" :key="id" :value="String(id)">
                    {{ name }}
                </option>
            </select>
            <button type="button" class="reviews-widget__btn" @click="onShowClick">
                Показать
            </button>
        </div>

        <div class="reviews-widget__list">
            <article class="reviews-widget__item" v-for="item in displayedItems" :key="item.id">
                <div class="reviews-widget__head">
                    <strong>{{ item.name }}</strong>
                    <span class="reviews-widget__date">{{ item.date }}</span>
                </div>
                <div class="reviews-widget__ship" v-if="item.ship_name">{{ item.ship_name }}</div>
                <div
                    v-if="item.trip_date || item.exp_rest"
                    class="reviews-widget__meta"
                >
                    <span v-if="item.trip_date" class="reviews-widget__meta-part">{{ item.trip_date }}</span>
                    <span v-if="item.trip_date && item.exp_rest" class="reviews-widget__meta-sep">·</span>
                    <span v-if="item.exp_rest" class="reviews-widget__meta-part">{{ item.exp_rest }}</span>
                </div>
                <div v-if="item.ratings && item.ratings.length" class="reviews-widget__ratings">
                    <div
                        v-for="r in item.ratings"
                        :key="r.key"
                        class="reviews-widget__rating"
                    >
                        <span class="reviews-widget__rating-label">{{ r.label }}</span>
                        <span class="reviews-widget__stars" :title="r.value + ' из 5'">
                            <span
                                v-for="i in 5"
                                :key="i"
                                class="reviews-widget__star"
                                :class="{ 'reviews-widget__star--on': i <= r.value }"
                            >★</span>
                        </span>
                    </div>
                </div>
                <div class="reviews-widget__text">{{ item.text }}</div>
            </article>
        </div>

        <button type="button" class="reviews-widget__more" @click="loadMore">
            {{ moreButtonText }}
        </button>
    </section>
</template>

<script>
import axios from "axios";

export default {
    name: "ReviewsWidget",
    props: {
        initData: {
            type: Object,
            default: null,
        }
    },
    data() {
        return {
            ready: false,
            entityType: null,
            entityId: null,
            initialItems: [],
            extraItems: [],
            loadedIds: [],
            ships: {},
            selectedShipId: '',
            moreButtonText: 'Подгрузить отзывы',
        };
    },
    computed: {
        combinedItems() {
            return [...this.initialItems, ...this.extraItems];
        },
        /** При выбранном теплоходе скрываем привязанные отзывы других судов (фильтр раньше действовал только на подгрузку). */
        displayedItems() {
            const sid = this.selectedShipId;
            if (!sid) {
                return this.combinedItems;
            }
            return this.combinedItems.filter((item) => String(item.ship_id) === String(sid));
        }
    },
    mounted() {
        const init = this.initData;
        if (!init) {
            return;
        }

        this.entityType = init.entityType || null;
        this.entityId = Number(init.entityId || 0);
        this.initialItems = Array.isArray(init.items) ? init.items : [];
        this.loadedIds = Array.isArray(init.excludeIds)
            ? init.excludeIds.map(id => Number(id))
            : this.initialItems.map(item => Number(item.id));
        this.ships = init.ships || {};
        this.ready = true;
    },
    methods: {
        onShowClick() {
            this.extraItems = [];
            this.loadedIds = this.initialItems.map(item => Number(item.id));
            this.loadMore();
        },
        loadMore() {
            axios.post('/rivercrs/api/reviewsMore', {
                exclude_ids: this.loadedIds,
                ship_id: this.selectedShipId || null,
            }).then(({ data }) => {
                const items = Array.isArray(data.items) ? data.items : [];
                if (!items.length) {
                    return;
                }

                this.extraItems = [...this.extraItems, ...items];
                this.loadedIds = [...this.loadedIds, ...items.map(item => Number(item.id))];
                this.moreButtonText = 'ещё';
            });
        }
    }
}
</script>

<style lang="scss">
.reviews-widget {
    background: #f3f7fb;
    border-radius: 8px;
    padding: 16px;

    &__panel {
        display: flex;
        gap: 10px;
        margin-bottom: 16px;
    }

    &__select {
        flex: 1 1 auto;
        min-height: 38px;
        border: 1px solid #d3dde8;
        border-radius: 6px;
        padding: 0 10px;
    }

    &__btn,
    &__more {
        min-height: 38px;
        border: 0;
        border-radius: 6px;
        background: #177bc0;
        color: #fff;
        padding: 0 14px;
        cursor: pointer;
    }

    &__list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    &__item {
        background: #fff;
        border-radius: 6px;
        padding: 12px;
    }

    &__head {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 6px;
        align-items: baseline;
    }

    &__date {
        color: #6b7a8c;
        font-size: 13px;
        font-weight: 400;
        white-space: nowrap;
    }

    &__ship {
        color: #666;
        margin-bottom: 4px;
        font-size: 13px;
    }

    &__meta {
        color: #6b7a8c;
        font-size: 12px;
        line-height: 1.45;
        margin-bottom: 8px;
    }

    &__meta-sep {
        margin: 0 0.35em;
        opacity: 0.7;
    }

    &__ratings {
        display: flex;
        flex-wrap: wrap;
        gap: 8px 14px;
        margin-bottom: 10px;
    }

    &__rating {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
    }

    &__rating-label {
        color: #5a6a7d;
    }

    &__stars {
        display: inline-flex;
        letter-spacing: 0.02em;
        user-select: none;
    }

    &__star {
        color: #d3dde8;
        font-size: 11px;
        line-height: 1;

        &--on {
            color: #177bc0;
        }
    }

    &__text {
        white-space: pre-line;
        font-size: 14px;
        line-height: 1.5;
        color: #222;
    }

    &__more {
        margin-top: 14px;
    }
}
</style>
