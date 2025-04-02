<template>
    <div v-if="images" class="zen-gallery" @click.self="images = null">
        <div class="zen-gallery__body">
            <div class="zen-gallery__header">
                <i @click="images = null" class="bi bi-x-circle"></i>
            </div>
            <div class="zen-gallery__main">
                <div class="zen-gallery__arrow">
                    <div @click="prev">
                        <i class="bi bi-chevron-left"></i>
                    </div>
                </div>
                <div class="zen-gallery__picture" :style="`background-image: url(${ images[index] })`"></div>
                <div class="zen-gallery__arrow">
                    <div @click="next">
                        <i class="bi bi-chevron-right"></i>
                    </div>
                </div>
            </div>
            <div class="zen-gallery__thumbs">
                <div
                    @click="index = i"
                    class="zen-gallery__thumb"
                    :class="{ active:(index === i) }"
                    v-for="(image, i) in images"
                    :style="`background-image: url(${ image })`"
                >
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "ZenGallery",
    mounted() {
        window.ZenGallery = this
    },
    data() {
        return {
            item: null,
            images: null,
            index: null,
        }
    },
    methods: {
        gallery(item)
        {
            this.item = $(item)
            this.index = this.item.index()
            this.getImages()
        },
        getImages()
        {
            this.images = this.item.closest('.zen-gallery-container')
                .find('.zen-gallery-item')
                .map(function (index, node) {
                    return $(node).attr('src')
                })
        },
        prev()
        {
            if (this.index - 1 > -1) {
                this.index --
            } else {
                this.index = this.images.length - 1
            }
        },
        next()
        {
            if (this.index + 1 >= this.images.length) {
                this.index = 0
            } else {
                this.index ++
            }
        }
    }
}
</script>

<style>
    @import 'zen-gallery.scss';
</style>
