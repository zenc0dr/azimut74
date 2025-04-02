<template>
    <div class="result-reviews">
        <template v-if="reviewsData && width">
            <div class="result-reviews__wrap" :style="`max-width:${ width }px`">
                <div class="result-reviews__title">{{ reviewsData.title }}</div>
                <swiper :pagination-visible="true" :pagination-clickable="true">
                    <div v-for="item in reviewsData.reviews" class="item">
                        <div class="result-reviews__item" :style="`max-width:${ width }px`">
                            <div class="result-reviews__head">
                                <div class="result-reviews__avatar" :style="`background-image:url(${ item.avatar })`"></div>
                                <div class="result-reviews__name">
                                    {{ item.name }}
                                </div>
                                <div class="result-reviews__date">
                                    {{ item.date }}
                                </div>
                            </div>
                            <div class="result-reviews__text">
                                {{ item.text }}
                            </div>
                        </div>
                    </div>
                </swiper>
            </div>
        </template>
    </div>
</template>

<script>
import {Swiper} from 'vue2-swiper'; // https://github.com/fchengjin/vue2-swiper
export default {
    name: "InjectReviews",
    components: { Swiper },
    data() {
        return {
            width: null,
        }
    },
    props: {
        reviewsData: null
    },
    mounted() {
        this.bindWidthDetect()
    },
    methods: {
        bindWidthDetect()
        {
            $(document).ready(() => {
                this.setWidth()
            })

            $(window).resize(() => {
                this.setWidth()
            })
        },
        setWidth()
        {
            this.width = $('.result-reviews').width()
        },
    }
}
</script>
<style lang="scss">
.result-reviews {
    &__wrap {
        background: #d7eafa;
        padding: 15px;
    }
    &__title {
        font-size: 20px;
        font-weight: bold;
        text-align: center;
        margin-bottom: 15px;
        color: #767676;
        margin-top: 15px;
    }
    &__item {
        display: flex;
        flex-direction: column;
        position: relative;
    }
    &__head {
        display: flex;
        flex-direction: row;
        align-items: center;
        background: #fff;
        border-radius: 40px;
    }
    &__avatar {
        width: 80px;
        height: 80px;
        background-position: center;
        background-size: cover;
        border-radius: 50%;
        margin-right: 20px;
    }
    &__name {
        font-weight: bold;
        margin-right: 10px;
    }
    &__date {
        color: red;
        font-size: 13px;
        margin-top: 4px;
    }
    &__text {
        font-size: 14px;
        margin-top: 20px;
        line-height: 21px;
        max-height: 100px;
        overflow-y: auto;
        margin-bottom: 50px;
        padding-right: 20px;
    }
    &__text::-webkit-scrollbar {
        width: 12px;
    }

    &__text::-webkit-scrollbar-thumb {
        border-width: 1px;
        background-color: #adbcc7;
        border-radius: 5px;
    }

    &__text::-webkit-scrollbar-thumb:hover {
        border-width: 1px;
        background-color: #adbcc7;
    }

    &__text::-webkit-scrollbar-track {
        border-width: 0;
        background: #d2e1ed;
        border-radius: 5px;
    }
}
</style>
