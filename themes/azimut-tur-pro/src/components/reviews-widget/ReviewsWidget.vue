<template>
    <section class="reviews-widget mt-4" v-if="ready">
        <div class="reviews-widget__panel">
            <select
                v-model="selectedShipId"
                class="reviews-widget__select"
                @change="onShipFilterChange"
            >
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
                    <span v-if="item.trip_date" class="reviews-widget__pill">
                        <span class="reviews-widget__pill-label">Дата рейса</span>
                        <span class="reviews-widget__pill-value">{{ item.trip_date }}</span>
                    </span>
                    <span v-if="item.exp_rest" class="reviews-widget__pill">
                        <span class="reviews-widget__pill-label">Ранее на теплоходах</span>
                        <span class="reviews-widget__pill-value">{{ item.exp_rest }}</span>
                    </span>
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

        <button
            type="button"
            class="reviews-widget__more"
            :disabled="moreRemaining <= 0"
            @click="loadMore"
        >
            <span class="reviews-widget__more-inner">
                <span class="reviews-widget__more-label">{{ moreButtonLabel }}</span>
                <span
                    v-if="moreRemaining > 0"
                    class="reviews-widget__more-badge"
                    aria-hidden="true"
                >{{ moreRemaining }}</span>
            </span>
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
            moreRemaining: 0,
            moreFirstTime: true,
        };
    },
    computed: {
        moreButtonLabel() {
            if (this.moreRemaining <= 0) {
                return this.moreFirstTime && !this.extraItems.length
                    ? 'Нет дополнительных отзывов'
                    : 'Все отзывы загружены';
            }
            return this.moreFirstTime ? 'Подгрузить отзывы' : 'ещё';
        },
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
        const mr = Number(init.moreRemaining);
        this.moreRemaining = Number.isFinite(mr) && mr >= 0 ? mr : 0;
        this.ready = true;
    },
    methods: {
        onShipFilterChange() {
            this.fetchRemaining();
        },
        fetchRemaining() {
            axios.post('/rivercrs/api/reviewsCount', {
                exclude_ids: this.loadedIds,
                ship_id: this.selectedShipId || null,
            }).then(({ data }) => {
                if (typeof data.remaining === 'number') {
                    this.moreRemaining = data.remaining;
                }
            });
        },
        onShowClick() {
            this.extraItems = [];
            this.loadedIds = this.initialItems.map(item => Number(item.id));
            this.moreFirstTime = true;
            this.loadMore();
        },
        loadMore() {
            axios.post('/rivercrs/api/reviewsMore', {
                exclude_ids: this.loadedIds,
                ship_id: this.selectedShipId || null,
            }).then(({ data }) => {
                const items = Array.isArray(data.items) ? data.items : [];
                if (typeof data.remaining === 'number') {
                    this.moreRemaining = data.remaining;
                }
                if (items.length) {
                    this.extraItems = [...this.extraItems, ...items];
                    this.loadedIds = [...this.loadedIds, ...items.map(item => Number(item.id))];
                    this.moreFirstTime = false;
                }
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

        &:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }
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
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: stretch;
        margin-bottom: 10px;
    }

    &__pill {
        display: inline-flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 2px;
        padding: 6px 12px 7px;
        border-radius: 10px;
        background: linear-gradient(180deg, #fbfcfe 0%, #eef4fb 100%);
        border: 1px solid #dfe8f2;
        box-shadow: 0 1px 2px rgba(23, 123, 192, 0.07);
        max-width: 100%;
    }

    &__pill-label {
        font-size: 10px;
        font-weight: 600;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #6b7a8c;
        line-height: 1.2;
    }

    &__pill-value {
        font-size: 13px;
        font-weight: 600;
        color: #1e3a52;
        line-height: 1.35;
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

        &-inner {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        &-label {
            line-height: 1.25;
        }

        &-badge {
            min-width: 1.6em;
            padding: 2px 9px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.22);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.35);
            font-weight: 700;
            font-size: 13px;
            line-height: 1.35;
            letter-spacing: 0.02em;
        }
    }
}
</style>
