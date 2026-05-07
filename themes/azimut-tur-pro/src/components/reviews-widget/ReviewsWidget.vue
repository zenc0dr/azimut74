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
            <article class="reviews-widget__item" v-for="item in combinedItems" :key="item.id">
                <div class="reviews-widget__head">
                    <strong>{{ item.name }}</strong>
                    <span>{{ item.date }}</span>
                </div>
                <div class="reviews-widget__ship" v-if="item.ship_name">{{ item.ship_name }}</div>
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
    }

    &__ship {
        color: #666;
        margin-bottom: 4px;
        font-size: 13px;
    }

    &__text {
        white-space: pre-line;
    }

    &__more {
        margin-top: 14px;
    }
}
</style>
