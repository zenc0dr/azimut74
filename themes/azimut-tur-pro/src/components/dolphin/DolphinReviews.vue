<template>
    <div v-if="reviews" class="dolphin-reviews">
        <h2 class="dolphin-reviews__title title-section">Отзывы наших клиентов</h2>
        <swiper :pagination-visible="true" :pagination-clickable="true">
            <div v-for="item in reviews" class="item">
                <div class="side">
                    <div class="image" :style="`background-image:url(${ item.image })`"></div>
                </div>
                <div class="side-center">
                    <div class="avatar" :style="`background-image:url(${ item.avatar })`"></div>
                    <div class="name">{{ item.name }}</div>
                </div>
                <div class="side">
                    <div class="header">
                        <div class="stars">
                            <i v-for="i in item.stars" class="bi bi-star-fill"></i>
                        </div>
                        <div class="info">
                            <div class="date">
                                {{ item.date }}
                            </div>
                            <div class="time">
                                {{ item.time }}
                            </div>
                            <a class="link" :href="item.link" target="_blank">
                                <i class="bi bi-link"></i>
                            </a>
                        </div>
                    </div>
                    <div class="text">
                        {{ item.text }}
                    </div>
                </div>
            </div>
        </swiper>
    </div>
</template>

<script>
import {Swiper} from 'vue2-swiper'; // https://github.com/fchengjin/vue2-swiper
export default {
    name: "DolphinReviews",
    components: {
        Swiper
    },
    created()
    {
        let reviews = $('meta[name="dolphin-reviews"]').attr('content')
        if (reviews) {
            this.reviews = JSON.parse(reviews)
        }
    },
    mounted() {
    //  setTimeout(() => {console.log("this is the third message")}, 1000);
    },
    data()
    {
        return {
            reviews: null
        }
    }
}
</script>
