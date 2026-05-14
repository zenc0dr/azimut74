<template>
    <section class="reviews-widget mt-4" v-if="ready">
        <h2 class="reviews-widget__heading">Отзывы</h2>
        <div class="reviews-widget__panel">
            <div class="reviews-widget__dropdown">
                <Dropdown
                    v-model="selectedShipId"
                    class="reviews-widget__ship-dropdown"
                    :options="shipOptions"
                    option-label="label"
                    option-value="id"
                    placeholder="Все теплоходы"
                    :filter="true"
                    filter-placeholder="Поиск теплохода…"
                    empty-filter-message="Ничего не найдено"
                    :show-clear="true"
                    @input="onShipSelect"
                />
            </div>
            <button
                type="button"
                class="reviews-widget__btn reviews-widget__btn--link"
                @click="openReviewModal"
            >
                Оставить отзыв
            </button>
        </div>

        <div class="reviews-widget__list">
            <article
                v-for="item in displayedItems"
                :key="item.id"
                class="reviews-item"
            >
                <div class="reviews-item-person">
                    <div class="reviews-item-person-img img" aria-hidden="true">
                        <svg
                            class="reviews-item-person-svg"
                            viewBox="0 0 48 48"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <circle cx="24" cy="24" r="24" fill="#3d2d6b"/>
                            <circle cx="24" cy="18" r="8" fill="#fff" opacity="0.95"/>
                            <ellipse cx="24" cy="38" rx="14" ry="10" fill="#fff" opacity="0.95"/>
                        </svg>
                    </div>
                    <div class="reviews-item-person-info">
                        <div class="reviews-item-person-name">{{ item.name }}</div>
                        <div v-if="item.ship_name" class="reviews-item-person-text">
                            <b>Теплоход:</b>
                            <span>
                                <a
                                    v-if="shipHref(item)"
                                    :href="shipHref(item)"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >{{ item.ship_name }}</a>
                                <template v-else> {{ item.ship_name }}</template>
                            </span>
                        </div>
                        <div
                            v-if="item.date_ru || item.date"
                            class="reviews-item-person-text"
                        >
                            <b>Дата отзыва:</b>
                            <span> {{ item.date_ru || item.date }}</span>
                        </div>
                        <div v-if="item.exp_rest" class="reviews-item-person-text">
                            <b>Опыт:</b>
                            <span> {{ experienceLabel(item.exp_rest) }}</span>
                        </div>
                        <div v-if="item.trip_date" class="reviews-item-person-text">
                            <b>Время отдыха:</b>
                            <span> {{ item.trip_date }}</span>
                        </div>
                    </div>
                </div>

                <div class="reviews-item-split">
                    <div class="reviews-item-col">
                        <div
                            class="reviews-item-text"
                            :class="{ 'reviews-item-text--clamped': commentClamped(item) }"
                        >
                            <div class="reviews-item-text-name">Комментарий</div>
                            <p>{{ item.text }}</p>
                        </div>

                        <div
                            v-if="item.ratings && item.ratings.length"
                            class="reviews-item-statistic hide-min-L"
                        >
                            <div class="reviews-item-statistic-header">
                                <div class="reviews-item-statistic-header-numb">{{ formatAvg(averageRating(item)) }}</div>
                                <div class="reviews-item-statistic-header-stars">
                                    <span
                                        v-for="n in 5"
                                        :key="'ms-' + item.id + '-' + n"
                                        class="reviews-item-star-wrap"
                                    >
                                        <svg
                                            v-if="starKind(averageRating(item), n) === 'fill'"
                                            class="reviews-item-star reviews-item-star--fill"
                                            viewBox="0 0 20 20"
                                            aria-hidden="true"
                                        >
                                            <path d="M10 1.5l2.6 5.3 5.9.9-4.3 4.2 1 5.9L10 15.9 4.8 17.8l1-5.9-4.3-4.2 5.9-.9L10 1.5z"/>
                                        </svg>
                                        <svg
                                            v-else-if="starKind(averageRating(item), n) === 'half'"
                                            class="reviews-item-star reviews-item-star--half"
                                            viewBox="0 0 20 20"
                                            aria-hidden="true"
                                        >
                                            <defs>
                                                <linearGradient :id="'h-' + item.id + '-' + n" x1="0" x2="1" y1="0" y2="0">
                                                    <stop offset="50%" stop-color="#1a8f4a"/>
                                                    <stop offset="50%" stop-color="#8aa89a"/>
                                                </linearGradient>
                                            </defs>
                                            <path :fill="'url(#h-' + item.id + '-' + n + ')'" d="M10 1.5l2.6 5.3 5.9.9-4.3 4.2 1 5.9L10 15.9 4.8 17.8l1-5.9-4.3-4.2 5.9-.9L10 1.5z"/>
                                        </svg>
                                        <svg
                                            v-else
                                            class="reviews-item-star reviews-item-star--outline"
                                            viewBox="0 0 20 20"
                                            aria-hidden="true"
                                        >
                                            <path fill="none" stroke="#8aa89a" stroke-width="1.4" d="M10 2.2l2.2 4.5 5 .8-3.6 3.5.9 5-4.5-2.4-4.5 2.4.9-5-3.6-3.5 5-.8 2.2-4.5z"/>
                                        </svg>
                                    </span>
                                </div>
                            </div>
                            <div class="reviews-item-statistic-list">
                                <div
                                    v-for="r in item.ratings"
                                    :key="'ml-' + item.id + '-' + r.key"
                                    class="reviews-item-statistic-list-item"
                                >
                                    <div class="reviews-item-statistic-list-item-text">
                                        <span>{{ ratingRowLabel(r) }}</span>
                                        <span>{{ formatRatingDecimal(r.value) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button
                            v-if="needsExpandToggle(item)"
                            type="button"
                            class="reviews-hide_btn"
                            @click="toggleExpand(item.id)"
                        >
                            <span class="reviews-hide_btn-text">{{ isExpanded(item.id) ? 'Свернуть' : 'Развернуть' }}</span>
                            <span
                                class="reviews-hide_btn-ico"
                                :class="{ 'reviews-hide_btn-ico--up': isExpanded(item.id) }"
                                aria-hidden="true"
                            >
                                <svg viewBox="0 0 12 12" width="12" height="12" xmlns="http://www.w3.org/2000/svg">
                                    <path fill="currentColor" d="M6 8.5L1 3h10z"/>
                                </svg>
                            </span>
                        </button>
                    </div>

                    <div
                        v-if="item.ratings && item.ratings.length"
                        class="reviews-item-statistic hide-L"
                    >
                        <div class="reviews-item-statistic-header">
                            <div class="reviews-item-statistic-header-numb">{{ formatAvg(averageRating(item)) }}</div>
                            <div class="reviews-item-statistic-header-stars">
                                <span
                                    v-for="n in 5"
                                    :key="'ds-' + item.id + '-' + n"
                                    class="reviews-item-star-wrap"
                                >
                                    <svg
                                        v-if="starKind(averageRating(item), n) === 'fill'"
                                        class="reviews-item-star reviews-item-star--fill"
                                        viewBox="0 0 20 20"
                                        aria-hidden="true"
                                    >
                                        <path d="M10 1.5l2.6 5.3 5.9.9-4.3 4.2 1 5.9L10 15.9 4.8 17.8l1-5.9-4.3-4.2 5.9-.9L10 1.5z"/>
                                    </svg>
                                    <svg
                                        v-else-if="starKind(averageRating(item), n) === 'half'"
                                        class="reviews-item-star reviews-item-star--half"
                                        viewBox="0 0 20 20"
                                        aria-hidden="true"
                                    >
                                        <defs>
                                            <linearGradient :id="'hd-' + item.id + '-' + n" x1="0" x2="1" y1="0" y2="0">
                                                <stop offset="50%" stop-color="#1a8f4a"/>
                                                <stop offset="50%" stop-color="#8aa89a"/>
                                            </linearGradient>
                                        </defs>
                                        <path :fill="'url(#hd-' + item.id + '-' + n + ')'" d="M10 1.5l2.6 5.3 5.9.9-4.3 4.2 1 5.9L10 15.9 4.8 17.8l1-5.9-4.3-4.2 5.9-.9L10 1.5z"/>
                                    </svg>
                                    <svg
                                        v-else
                                        class="reviews-item-star reviews-item-star--outline"
                                        viewBox="0 0 20 20"
                                        aria-hidden="true"
                                    >
                                        <path fill="none" stroke="#8aa89a" stroke-width="1.4" d="M10 2.2l2.2 4.5 5 .8-3.6 3.5.9 5-4.5-2.4-4.5 2.4.9-5-3.6-3.5 5-.8 2.2-4.5z"/>
                                    </svg>
                                </span>
                            </div>
                        </div>
                        <div class="reviews-item-statistic-list">
                            <div
                                v-for="r in item.ratings"
                                :key="'dl-' + item.id + '-' + r.key"
                                class="reviews-item-statistic-list-item"
                            >
                                <div class="reviews-item-statistic-list-item-text">
                                    <span>{{ ratingRowLabel(r) }}</span>
                                    <span>{{ formatRatingDecimal(r.value) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </article>
        </div>

        <div class="reviews-widget__footer">
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
            <button
                type="button"
                class="reviews-widget__btn reviews-widget__btn--link reviews-widget__footer-review-link"
                @click="openReviewModal"
            >
                Оставить отзыв
            </button>
        </div>

        <div
            v-if="reviewModalOpen"
            class="reviews-widget__modal-backdrop"
            tabindex="-1"
            ref="reviewModalBackdrop"
            role="dialog"
            aria-modal="true"
            aria-labelledby="reviews-widget-review-modal-title"
            @click.self="closeReviewModal"
            @keyup.esc="closeReviewModal"
        >
            <div class="reviews-widget__modal" @click.stop>
                <div class="reviews-widget__modal-head" ref="reviewModalHead">
                    <h3 id="reviews-widget-review-modal-title" class="reviews-widget__modal-title">
                        Оставить отзыв
                    </h3>
                    <button
                        type="button"
                        class="reviews-widget__modal-close"
                        aria-label="Закрыть"
                        @click="closeReviewModal"
                    >
                        ×
                    </button>
                </div>
                <iframe
                    ref="reviewIframe"
                    :key="reviewModalIframeKey"
                    class="reviews-widget__modal-iframe"
                    src="/reviews-modal"
                    title="Форма отзыва"
                    @load="onReviewIframeLoad"
                />
            </div>
        </div>
    </section>
</template>

<script>
import axios from "axios";
import Dropdown from "primevue/dropdown";

const COMMENT_CLAMP_CHARS = 320;

export default {
    name: "ReviewsWidget",
    components: {
        Dropdown,
    },
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
            /** @type {Array<{id:number,label:string}>} */
            shipOptions: [],
            selectedShipId: null,
            moreRemaining: 0,
            moreFirstTime: true,
            expanded: {},
            reviewModalOpen: false,
            reviewModalIframeKey: 0,
            /** @type {ResizeObserver|null} */
            reviewIframeResizeObserver: null,
            reviewIframeOnWindowResize: null,
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
        displayedItems() {
            const sid = this.selectedShipId;
            if (sid == null || sid === "") {
                return this.combinedItems;
            }
            return this.combinedItems.filter((item) => String(item.ship_id) === String(sid));
        },
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
        this.shipOptions = this.normalizeShipOptions(init.ships);
        const mr = Number(init.moreRemaining);
        this.moreRemaining = Number.isFinite(mr) && mr >= 0 ? mr : 0;
        this.ready = true;
    },
    beforeDestroy() {
        this.clearReviewIframeHeightSync();
        if (this.reviewModalOpen) {
            document.body.style.overflow = "";
        }
    },
    watch: {
        reviewModalOpen(open) {
            this.$nextTick(() => {
                if (open && this.$refs.reviewModalBackdrop) {
                    this.$refs.reviewModalBackdrop.focus();
                }
            });
        },
    },
    methods: {
        /**
         * API: массив { id, label } или устаревший объект { id: name }.
         * @param {Array|Object|null} raw
         * @returns {Array<{id:number,label:string}>}
         */
        normalizeShipOptions(raw) {
            if (!raw) {
                return [];
            }
            if (Array.isArray(raw)) {
                return raw
                    .map((row) => ({
                        id: Number(row.id),
                        label: String(row.label != null ? row.label : "").trim() || String(row.id),
                    }))
                    .filter((row) => Number.isFinite(row.id));
            }
            return Object.keys(raw)
                .map((k) => ({
                    id: Number(k),
                    label: String(raw[k] || "").trim() || k,
                }))
                .filter((row) => Number.isFinite(row.id))
                .sort((a, b) => a.label.localeCompare(b.label, "ru", { sensitivity: "base" }));
        },
        shipPayloadId() {
            const sid = this.selectedShipId;
            if (sid == null || sid === "") {
                return null;
            }
            const n = Number(sid);
            return Number.isFinite(n) ? n : null;
        },
        shipHref(item) {
            if (!item.ship_id) {
                return null;
            }
            return `/russia-river-cruises/motorship/${item.ship_id}`;
        },
        experienceLabel(text) {
            const t = String(text || '').trim();
            if (t === 'Первый раз') {
                return 'первый раз в круизе';
            }
            return t.charAt(0).toLowerCase() + t.slice(1);
        },
        averageRating(item) {
            if (!item.ratings || !item.ratings.length) {
                return null;
            }
            const sum = item.ratings.reduce((a, r) => a + Number(r.value), 0);
            return Math.round((sum / item.ratings.length) * 10) / 10;
        },
        formatAvg(avg) {
            if (avg == null || Number.isNaN(avg)) {
                return '—';
            }
            return String(avg).replace('.', ',');
        },
        formatRatingDecimal(value) {
            return String(value).replace('.', ',');
        },
        /** 'fill' | 'half' | 'outline' — как на референсе (звёзды SVG). */
        starKind(avg, n) {
            if (avg == null) {
                return 'outline';
            }
            const full = Math.floor(avg + 1e-9);
            const frac = avg - full;
            if (n <= full) {
                return 'fill';
            }
            if (n === full + 1 && frac >= 0.05) {
                return 'half';
            }
            return 'outline';
        },
        /** Подпись строки шкалы: последняя — «Теплоход» как в макете заказчика. */
        ratingRowLabel(r) {
            return r.key === 'cruise' ? 'Теплоход' : r.label;
        },
        needsExpandToggle(item) {
            const t = String(item.text || '');
            return t.length > COMMENT_CLAMP_CHARS || (t.match(/\n/g) || []).length >= 4;
        },
        commentClamped(item) {
            return this.needsExpandToggle(item) && !this.isExpanded(item.id);
        },
        isExpanded(id) {
            return !!this.expanded[id];
        },
        toggleExpand(id) {
            this.$set(this.expanded, id, !this.expanded[id]);
        },
        openReviewModal() {
            this.reviewModalIframeKey += 1;
            this.reviewModalOpen = true;
            document.body.style.overflow = "hidden";
            this.$nextTick(() => {
                const bd = this.$refs.reviewModalBackdrop;
                if (bd) {
                    bd.scrollTop = 0;
                }
            });
        },
        closeReviewModal() {
            this.clearReviewIframeHeightSync();
            const iframe = this.$refs.reviewIframe;
            if (iframe) {
                iframe.style.height = "";
            }
            this.reviewModalOpen = false;
            document.body.style.overflow = "";
        },
        /**
         * Значение из emit Dropdown (до/после v-model) — явно синхронизируем, чтобы ship_id в запросах совпадал с фильтром.
         * @param {number|string|null} shipId
         */
        onShipSelect(shipId) {
            this.selectedShipId = shipId;
            this.extraItems = [];
            this.loadedIds = this.initialItems.map((item) => Number(item.id));
            this.moreFirstTime = true;
            this.fetchRemaining();
            this.loadMore();
        },
        fetchRemaining() {
            return axios
                .post("/rivercrs/api/reviewsCount", {
                    exclude_ids: this.loadedIds,
                    ship_id: this.shipPayloadId(),
                })
                .then(({ data }) => {
                    if (typeof data.remaining === "number") {
                        this.moreRemaining = data.remaining;
                    }
                });
        },
        loadMore() {
            axios.post('/rivercrs/api/reviewsMore', {
                exclude_ids: this.loadedIds,
                ship_id: this.shipPayloadId(),
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
        },
        /**
         * Высота iframe = высота документа внутри (только same-origin), без внутреннего скролла iframe —
         * прокрутка на подложке модалки. Без доступа к document остаётся min-height из CSS.
         */
        onReviewIframeLoad() {
            this.$nextTick(() => {
                this.setupReviewIframeHeightSync();
            });
        },
        setupReviewIframeHeightSync() {
            this.clearReviewIframeHeightSync();
            const iframe = this.$refs.reviewIframe;
            if (!iframe || !this.reviewModalOpen) {
                return;
            }

            const run = () => {
                if (!this.reviewModalOpen) {
                    return;
                }
                const el = this.$refs.reviewIframe;
                if (!el) {
                    return;
                }

                try {
                    const doc = el.contentDocument || el.contentWindow?.document;
                    if (!doc || !doc.documentElement) {
                        el.style.height = "";
                        return;
                    }
                    const body = doc.body;
                    const html = doc.documentElement;
                    const innerH = Math.max(
                        body ? body.scrollHeight : 0,
                        html ? html.scrollHeight : 0,
                        body ? body.offsetHeight : 0,
                        html ? html.offsetHeight : 0,
                    );
                    if (innerH > 0) {
                        el.style.height = `${innerH}px`;
                    }
                } catch (e) {
                    el.style.height = "";
                }
            };

            try {
                const doc = iframe.contentDocument || iframe.contentWindow?.document;
                if (doc && typeof ResizeObserver !== "undefined") {
                    const ro = new ResizeObserver(() => run());
                    ro.observe(doc.documentElement);
                    if (doc.body) {
                        ro.observe(doc.body);
                    }
                    this.reviewIframeResizeObserver = ro;
                }
            } catch (e) {
                this.reviewIframeResizeObserver = null;
            }

            this.reviewIframeOnWindowResize = () => run();
            window.addEventListener("resize", this.reviewIframeOnWindowResize);
            run();
        },
        clearReviewIframeHeightSync() {
            if (this.reviewIframeResizeObserver) {
                this.reviewIframeResizeObserver.disconnect();
                this.reviewIframeResizeObserver = null;
            }
            if (this.reviewIframeOnWindowResize) {
                window.removeEventListener("resize", this.reviewIframeOnWindowResize);
                this.reviewIframeOnWindowResize = null;
            }
        },
    }
}
</script>

<style lang="scss">
/* Карточка: классы как в референсе (review_card_html.md), стили изолированы под .reviews-widget */

.reviews-widget {
    --rw-page-bg: #f4f5fa;
    --rw-card-bg: #f1f2f8;
    --rw-title: #2d2d5a;
    --rw-muted: #6b6f8a;
    --rw-body: #4a4d6b;
    --rw-link: #177bc0;
    --rw-green: #1a8f4a;
    --rw-star-outline: #8aa89a;
    --rw-stat-label: var(--rw-title);
    --rw-stat-value: var(--rw-title);

    background: var(--rw-page-bg);
    border-radius: 12px;
    padding: 16px;

    &__heading {
        margin: 0 0 14px;
        font-size: 22px;
        font-weight: 700;
        line-height: 1.2;
        color: var(--rw-title);
    }

    .reviews-item {
        position: relative;
        background: var(--rw-card-bg);
        border-radius: 6px;
        padding: 16px;
        color: var(--rw-title);
        box-shadow: none;
    }

    @media only screen and (min-width: 767px) {
        .reviews-item {
            padding: 24px 40px;
        }
    }

    .reviews-item-person {
        display: flex;
        align-items: center;
        margin-bottom: 26px;
    }

    .reviews-item-person-img {
        flex-shrink: 0;
        width: 74px;
        height: 74px;
        margin-right: 20px;

        &.img {
            border-radius: 50%;
            overflow: hidden;
        }
    }

    @media only screen and (max-width: 766px) {
        .reviews-item-person-img {
            display: none;
        }
    }

    .reviews-item-person-svg {
        display: block;
        width: 100%;
        height: 100%;
    }

    .reviews-item-person-name {
        font-weight: 700;
        font-size: 18px;
        line-height: 120%;
        margin-bottom: 0;
        color: var(--rw-title);
    }

    .reviews-item-person-info {
        font-size: 14px;
        line-height: 151%;
        font-weight: 300;

        b {
            font-weight: 700;
        }
    }

    @media only screen and (max-width: 766px) {
        .reviews-item-person-info {
            width: calc(100% - 110px);
        }
    }

    .reviews-item-person-text {
        margin-bottom: 0;
        color: inherit;

        b {
            color: var(--rw-title);
            font-weight: 700;
        }

        a {
            color: var(--rw-link);
            text-decoration: underline;
            text-underline-offset: 2px;

            &:hover {
                text-decoration: none;
            }
        }
    }

    .reviews-item-split {
        display: flex;
    }

    @media only screen and (max-width: 766px) {
        .reviews-item-split {
            flex-direction: column-reverse;
        }
    }

    .reviews-item-col {
        min-width: 0;
    }

    @media only screen and (min-width: 767px) {
        .reviews-item-col {
            width: calc(100% - 289px);
        }
    }

    .reviews-item-text {
        color: var(--rw-body);
        font-size: 14px;
        line-height: 22px;
        font-weight: 400;
        font-style: normal;
        overflow: hidden;
        width: 100%;
        will-change: height;

        p {
            margin: 0;
            white-space: pre-line;
        }

        &--clamped {
            max-height: 120px;
            overflow: hidden;
            position: relative;

            &::after {
                content: '';
                position: absolute;
                left: 0;
                right: 0;
                bottom: 0;
                height: 36px;
                background: linear-gradient(to bottom, rgba(240, 241, 248, 0), var(--rw-card-bg));
            }
        }
    }

    @media only screen and (min-width: 767px) {
        .reviews-item-text {
            width: calc(100% + 289px);
            margin-right: -289px;
            padding-right: 289px;
        }
    }

    .reviews-item-text-name {
        font-weight: 700;
        font-size: 16px;
        line-height: 151%;
        color: var(--rw-title);
        margin-bottom: 8px;
    }

    .reviews-hide_btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-top: 12px;
        padding: 0;
        border: 0;
        background: transparent;
        color: var(--rw-link);
        font-size: 14px;
        line-height: 120%;
        font-weight: 700;
        font-style: normal;
        transition: 0s;
        cursor: pointer;
    }

    @media only screen and (min-width: 767px) {
        .reviews-hide_btn {
            margin-top: 20px;
        }
    }

    .reviews-hide_btn-ico {
        display: flex;
        color: var(--rw-link);
        transition: transform 0.2s ease;

        &--up {
            transform: rotate(180deg);
        }
    }

    /* Плоский блок оценок: белая «карточка» без теней */
    .reviews-item-statistic {
        background: #fff;
        border-radius: 10px;
        padding: 14px 18px 18px;
        box-sizing: border-box;
        box-shadow: none;
    }

    .reviews-item-statistic-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0;
        margin-bottom: 16px;
    }

    @media only screen and (max-width: 766px) {
        .reviews-item-statistic-header {
            flex-direction: column;
            padding: 0 0 6px;
            text-align: center;
            border-radius: 0;
            box-shadow: none;
            gap: 12px;
            position: absolute;
            top: 20px;
            right: 20px;
            order: -1;
        }
    }

    .reviews-item-statistic-header-numb {
        font-size: 36px;
        font-weight: 800;
        line-height: 20px;
        letter-spacing: 0;
        color: var(--rw-title);
    }

    .reviews-item-statistic-header-stars {
        display: flex;
        gap: 4px;
        align-items: center;
        margin-top: 8px;
        padding-top: 0;
        color: var(--rw-green);
    }

    .reviews-item-star-wrap {
        display: flex;
        width: 24px;
        height: 24px;
    }

    .reviews-item-star {
        width: 24px;
        height: 24px;

        &--fill path {
            fill: var(--rw-green);
        }

        &--outline path {
            stroke: var(--rw-star-outline);
        }
    }

    @media only screen and (max-width: 766px) {
        .reviews-item-statistic-header-stars .reviews-item-star-wrap,
        .reviews-item-statistic-header-stars .reviews-item-star {
            width: 15px;
            height: 15px;
        }
    }

    .reviews-item-statistic-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .reviews-item-statistic-list-item-text {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        margin-top: 0;
        padding: 2px 0;
        gap: 12px;
        font-size: 14px;
        line-height: 1.4;

        span:first-child {
            font-weight: inherit;
            color: var(--rw-stat-label);
            padding-right: 0;
        }

        span:last-child {
            font-weight: inherit;
            color: var(--rw-stat-value);
            white-space: nowrap;
        }
    }

    /* Референс: блок оценок под текстом — только на узком экране */
    .reviews-item-statistic.hide-min-L {
        display: none;
        margin-top: 12px;
        margin-bottom: 24px;
        padding-top: 0;
    }

    /* Колонка оценок справа: геометрия из cruise_style, без обнуления внутренних отступов */
    .reviews-item-statistic.hide-L {
        display: none;
        flex-shrink: 0;
        width: 225px;
        margin-top: -125px;
        margin-bottom: 24px;
        margin-left: 64px;
    }

    @media only screen and (max-width: 766px) {
        .reviews-item-statistic.hide-min-L {
            display: block;
        }
    }

    @media only screen and (min-width: 767px) {
        .reviews-item-statistic.hide-L {
            display: block;
        }
    }

    &__panel {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 16px;

        .reviews-widget__btn--link {
            background: #fff;
            color: #e12c2e;
            border: 1px solid #e12c2e;

            &:hover {
                background: #fce8e8;
                color: #e12c2e;
            }
        }
    }

    &__dropdown {
        flex: 1 1 220px;
        min-width: 0;
    }

    &__ship-dropdown {
        width: 100%;
    }

    &__btn,
    &__more {
        min-height: 38px;
        border: 0;
        border-radius: 8px;
        background: #177bc0;
        color: #fff;
        padding: 0 14px;
        cursor: pointer;
        font-family: inherit;

        &:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }
    }

    &__btn--link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        background: #fff;
        color: #e12c2e;
        border: 1px solid #e12c2e;
        box-sizing: border-box;
        font-family: inherit;

        &:hover {
            background: #fce8e8;
            text-decoration: none;
            color: #e12c2e;
        }
    }

    &__footer {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
        margin-top: 14px;
    }

    &__footer .reviews-widget__more {
        margin-top: 0;
    }

    &__footer-review-link {
        margin-left: auto;
    }

    &__modal-backdrop {
        position: fixed;
        inset: 0;
        z-index: 10050;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        overflow-x: hidden;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior: contain;
        padding: 16px 12px 32px;
        box-sizing: border-box;
        background: rgba(45, 45, 90, 0.45);
        outline: none;
    }

    &__modal {
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
        width: min(920px, 100%);
        background: #fff;
        border-radius: 0;
        overflow: visible;
        box-shadow: none;

        @media only screen and (min-width: 921px) {
            border-radius: 12px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2);
            width: min(920px, calc(100% - 24px));
        }
    }

    &__modal-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 16px;
        border-bottom: 1px solid #e0e4f0;
        flex-shrink: 0;
    }

    &__modal-title {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
        color: var(--rw-title);
    }

    &__modal-close {
        flex-shrink: 0;
        width: 40px;
        height: 40px;
        padding: 0;
        border: 0;
        border-radius: 8px;
        background: transparent;
        color: var(--rw-muted);
        font-size: 28px;
        line-height: 1;
        cursor: pointer;

        &:hover {
            background: var(--rw-page-bg);
            color: var(--rw-title);
        }
    }

    &__modal-iframe {
        display: block;
        width: 100%;
        min-height: 55vh;
        border: 0;
    }

    &__list {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    &__more {
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
